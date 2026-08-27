<?php

declare(strict_types=1);

namespace Tests\Feature\Returns;

use App\Enums\OrderStatus;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Exceptions\ReturnNotAllowedException;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Services\ReturnRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Returns and exchanges.
 *
 * The rule that carries the most weight: an approved full return goes through
 * UpdateOrderStatusAction, so the stock story stays in one place rather than
 * being duplicated here.
 */
class ReturnRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function service(): ReturnRequestService
    {
        return app(ReturnRequestService::class);
    }

    /**
     * A delivered order with one line, and stock behind it.
     *
     * @return array{0: Order, 1: OrderItem, 2: ProductVariant}
     */
    private function deliveredOrder(int $quantity = 2, string $deliveredAt = 'now'): array
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

        $order = Order::factory()->create([
            'user_id'      => $this->customer->id,
            'status'       => OrderStatus::Delivered,
            'delivered_at' => new \DateTimeImmutable($deliveredAt),
        ]);

        OrderAddress::factory()->for($order)->create();

        $item = OrderItem::factory()->for($order)->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => $quantity,
        ]);

        return [$order->refresh(), $item, $variant];
    }

    /**
     * Another sellable variant of the same product, to exchange for.
     */
    private function siblingVariant(ProductVariant $variant): ProductVariant
    {
        return ProductVariant::factory()->create([
            'product_id'     => $variant->product_id,
            'stock_quantity' => 5,
            'is_active'      => true,
        ]);
    }

    // ------------------------------------------------------------ Eligibility

    public function test_a_delivered_order_can_be_returned(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize, $this->customer,
        );

        $this->assertSame(ReturnStatus::Requested, $request->status);
        $this->assertSame(1, $request->totalQuantity());
        $this->assertStringStartsWith('RET-', $request->number);
    }

    public function test_an_undelivered_order_cannot_be_returned(): void
    {
        $order = Order::factory()->status(OrderStatus::Shipped)->create();
        $item = OrderItem::factory()->for($order)->create();

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->request(
            $order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize,
        );
    }

    public function test_the_returns_window_closes(): void
    {
        $days = (int) config('hoor.returns.window_days');

        [$order, $item] = $this->deliveredOrder(deliveredAt: '-'.($days + 1).' days');

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->request(
            $order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize,
        );
    }

    public function test_a_delivery_inside_the_window_is_still_returnable(): void
    {
        $days = (int) config('hoor.returns.window_days');

        [$order, $item] = $this->deliveredOrder(deliveredAt: '-'.($days - 1).' days');

        $this->assertTrue($order->isReturnable());

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize,
        );

        $this->assertNotNull($request->id);
    }

    // -------------------------------------------------------------- Quantities

    public function test_more_units_than_the_order_holds_are_refused(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 2);

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->request(
            $order, [$item->id => 3], ReturnType::Return_, ReturnReason::WrongSize,
        );
    }

    public function test_part_of_a_line_may_be_returned_and_then_the_rest(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 3);

        $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->request($order->refresh(), [$item->id => 2], ReturnType::Return_, ReturnReason::ChangedMind);

        $this->assertSame(3, array_sum($order->refresh()->returnedQuantities()));
    }

    public function test_a_second_request_cannot_exceed_what_is_left(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 3);

        $this->service()->request($order, [$item->id => 2], ReturnType::Return_, ReturnReason::WrongSize);

        $this->expectException(ReturnNotAllowedException::class);

        // Only one remains.
        $this->service()->request($order->refresh(), [$item->id => 2], ReturnType::Return_, ReturnReason::WrongSize);
    }

    public function test_a_rejected_request_frees_its_units_again(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 2);

        $request = $this->service()->request($order, [$item->id => 2], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->reject($request, $this->admin, 'Outside policy.');

        // Refused means the pieces stayed with her, so she may ask again.
        $this->assertSame([], $order->refresh()->returnedQuantities());

        $again = $this->service()->request($order, [$item->id => 2], ReturnType::Return_, ReturnReason::Damaged);
        $this->assertSame(ReturnStatus::Requested, $again->status);
    }

    public function test_a_request_with_no_pieces_is_refused(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $this->expectException(ReturnNotAllowedException::class);

        // Every line unticked.
        $this->service()->request($order, [$item->id => 0], ReturnType::Return_, ReturnReason::WrongSize);
    }

    public function test_a_line_from_another_order_is_refused(): void
    {
        [$order] = $this->deliveredOrder();
        $elsewhere = OrderItem::factory()->create();

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->request($order, [$elsewhere->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
    }

    // ---------------------------------------------------------- The decision

    /**
     * The rule that shapes the whole phase: approving is a promise, receiving
     * is a fact about inventory.
     */
    public function test_approving_does_not_move_stock(): void
    {
        [$order, $item, $variant] = $this->deliveredOrder(quantity: 2);

        $before = $variant->stock_quantity;

        $request = $this->service()->request($order, [$item->id => 2], ReturnType::Return_, ReturnReason::Damaged);
        $this->service()->approve($request, $this->admin);

        // Nothing has come back yet, so nothing is on the shelf.
        $this->assertSame($before, $variant->fresh()->stock_quantity);
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
    }

    public function test_receiving_a_full_return_marks_the_order_returned_and_restocks(): void
    {
        [$order, $item, $variant] = $this->deliveredOrder(quantity: 2);

        $before = $variant->stock_quantity;

        $request = $this->service()->request($order, [$item->id => 2], ReturnType::Return_, ReturnReason::Damaged);
        $this->service()->approve($request, $this->admin);
        $this->service()->receive($request->fresh(), $this->admin);

        // Through UpdateOrderStatusAction, which owns the restock.
        $this->assertSame(OrderStatus::Returned, $order->refresh()->status);
        $this->assertSame($before + 2, $variant->fresh()->stock_quantity);

        // And the order's own history records it.
        $this->assertSame(1, $order->statusHistory()->count());
    }

    /**
     * What arrives is not always what was promised.
     */
    public function test_receiving_fewer_than_requested_restocks_only_what_came_back(): void
    {
        [$order, $item, $variant] = $this->deliveredOrder(quantity: 3);

        $before = $variant->stock_quantity;

        $request = $this->service()->request($order, [$item->id => 3], ReturnType::Return_, ReturnReason::Damaged);
        $this->service()->approve($request, $this->admin);

        $line = $request->items->first();
        $this->service()->receive($request->fresh(), $this->admin, [$line->id => 1]);

        $this->assertSame($before + 1, $variant->fresh()->stock_quantity);

        // Two of the three never came back, so the order was not returned.
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
    }

    public function test_recording_more_than_was_requested_is_refused(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 2);

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::Damaged);
        $this->service()->approve($request, $this->admin);

        $line = $request->items->first();

        $this->expectException(ReturnNotAllowedException::class);

        // More in the box than the request covers is a counting error.
        $this->service()->receive($request->fresh(), $this->admin, [$line->id => 2]);
    }

    public function test_receiving_a_partial_return_leaves_the_order_delivered(): void
    {
        [$order, $item, $variant] = $this->deliveredOrder(quantity: 3);

        $before = $variant->stock_quantity;

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->approve($request, $this->admin);
        $this->service()->receive($request->fresh(), $this->admin);

        // Most of it was delivered and kept, which is what happened — but the
        // one piece that came back is on the shelf again.
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
        $this->assertSame($before + 1, $variant->fresh()->stock_quantity);
        $this->assertSame(ReturnStatus::Received, $request->fresh()->status);
    }

    public function test_a_received_exchange_does_not_restock_the_returned_piece(): void
    {
        [$order, $item, $variant] = $this->deliveredOrder(quantity: 2);
        $replacement = $this->siblingVariant($variant);

        $before = $variant->stock_quantity;

        $request = $this->service()->request(
            $order, [$item->id => 2], ReturnType::Exchange, ReturnReason::WrongSize,
            replacements: [$item->id => $replacement->id],
        );

        $this->service()->approve($request, $this->admin);
        $this->service()->receive($request->fresh(), $this->admin);

        // Swapped, not returned to sellable stock, so the original is unchanged
        // and the order was not returned.
        $this->assertSame($before, $variant->fresh()->stock_quantity);
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
    }

    public function test_a_decision_records_who_made_it(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->reject($request, $this->admin, 'Worn.');

        $request->refresh();

        $this->assertSame(ReturnStatus::Rejected, $request->status);
        $this->assertSame($this->admin->id, $request->decided_by);
        $this->assertSame('Worn.', $request->admin_note);
        $this->assertNotNull($request->decided_at);
    }

    public function test_a_rejected_request_cannot_be_decided_again(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->reject($request, $this->admin);

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->approve($request->fresh(), $this->admin);
    }

    /**
     * An approved parcel can still be refused when it arrives — worn, washed,
     * or not the piece described.
     */
    public function test_an_approved_request_may_still_be_rejected_on_arrival(): void
    {
        [$order, $item, $variant] = $this->deliveredOrder();

        $before = $variant->stock_quantity;

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->approve($request, $this->admin);
        $this->service()->reject($request->fresh(), $this->admin, 'Worn.');

        $this->assertSame(ReturnStatus::Rejected, $request->fresh()->status);
        $this->assertSame($before, $variant->fresh()->stock_quantity);
    }

    public function test_a_request_cannot_skip_straight_to_completed(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);

        $this->expectException(ReturnNotAllowedException::class);

        // The parcel has not even been approved, let alone arrived.
        $this->service()->complete($request, $this->admin);
    }

    // ------------------------------------------------------------ Withdrawal

    public function test_a_customer_may_withdraw_a_pending_request(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->withdraw($request);

        $this->assertDatabaseMissing('return_requests', ['id' => $request->id]);

        // Withdrawing frees the units, so she can raise a corrected request.
        $this->assertSame([], $order->refresh()->returnedQuantities());
    }

    public function test_a_decided_request_cannot_be_withdrawn(): void
    {
        [$order, $item] = $this->deliveredOrder();

        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);
        $this->service()->approve($request, $this->admin);
        // Once the shop has committed, she can no longer simply take it back.

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->withdraw($request->fresh());
    }

    // ------------------------------------------------------------------ HTTP

    public function test_a_customer_can_raise_a_request_through_the_form(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 2);

        $this->actingAs($this->customer)
            ->post(route('store.account.returns.store', ['locale' => 'en', 'order' => $order]), [
                'type'       => 'return',
                'reason'     => 'wrong_size',
                'note'       => 'Too tight at the waist.',
                // HTML posts strings, including the item id as an array key.
                'quantities' => [(string) $item->id => '2'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $order->returnRequests()->count());
    }

    public function test_the_form_reports_an_over_quantity_as_an_error_not_a_500(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 1);

        $this->actingAs($this->customer)
            ->post(route('store.account.returns.store', ['locale' => 'en', 'order' => $order]), [
                'type'       => 'return',
                'reason'     => 'wrong_size',
                'quantities' => [$item->id => 5],
            ])
            ->assertSessionHasErrors('quantities');

        $this->assertSame(0, $order->returnRequests()->count());
    }

    public function test_a_customer_cannot_raise_a_request_on_someone_elses_order(): void
    {
        $hers = Order::factory()->create([
            'user_id'      => User::factory()->create()->id,
            'status'       => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);
        $item = OrderItem::factory()->for($hers)->create();

        $this->actingAs($this->customer)
            ->post(route('store.account.returns.store', ['locale' => 'en', 'order' => $hers]), [
                'type'       => 'return',
                'reason'     => 'wrong_size',
                'quantities' => [$item->id => 1],
            ])
            ->assertNotFound();
    }

    public function test_a_customer_cannot_see_someone_elses_request(): void
    {
        $hers = ReturnRequest::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->customer)
            ->get(route('store.account.returns.show', ['locale' => 'en', 'return' => $hers]))
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- Admin

    public function test_staff_can_approve_from_the_queue(): void
    {
        [$order, $item] = $this->deliveredOrder(quantity: 2);

        $request = $this->service()->request($order, [$item->id => 2], ReturnType::Return_, ReturnReason::Damaged);

        $this->actingAs($this->admin)
            ->patch(route('admin.returns.decide', ['locale' => 'en', 'return' => $request]), [
                'decision' => 'approve',
                'note'     => 'Refunded on collection.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(ReturnStatus::Approved, $request->fresh()->status);

        // Approval alone does not return the order — the parcel has not arrived.
        $this->assertSame(OrderStatus::Delivered, $order->refresh()->status);
    }

    public function test_a_customer_cannot_reach_the_admin_queue(): void
    {
        $this->actingAs($this->customer)
            ->get(route('admin.returns.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    public function test_the_queue_counts_every_status(): void
    {
        ReturnRequest::factory()->count(2)->create();
        ReturnRequest::factory()->status(ReturnStatus::Approved)->create();

        $counts = $this->service()->countsByStatus();

        $this->assertSame(3, $counts['all']);
        $this->assertSame(2, $counts['requested']);
        $this->assertSame(1, $counts['approved']);
        $this->assertSame(0, $counts['rejected']);
    }

    public function test_the_admin_screens_render_in_both_locales(): void
    {
        [$order, $item] = $this->deliveredOrder();
        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize);

        foreach (['en', 'ar'] as $locale) {
            $this->actingAs($this->admin)
                ->get(route('admin.returns.index', ['locale' => $locale]))
                ->assertOk();

            $this->actingAs($this->admin)
                ->get(route('admin.returns.show', ['locale' => $locale, 'return' => $request]))
                ->assertOk();
        }
    }

    public function test_the_customer_screens_render_in_both_locales(): void
    {
        [$order, $item] = $this->deliveredOrder();
        $request = $this->service()->request($order, [$item->id => 1], ReturnType::Return_, ReturnReason::WrongSize, $this->customer);

        foreach (['en', 'ar'] as $locale) {
            foreach ([
                route('store.account.returns.index', ['locale' => $locale]),
                route('store.account.returns.show', ['locale' => $locale, 'return' => $request]),
                route('store.account.returns.create', ['locale' => $locale, 'order' => $order]),
            ] as $url) {
                $this->actingAs($this->customer)->get($url)->assertOk();
            }
        }
    }
}
