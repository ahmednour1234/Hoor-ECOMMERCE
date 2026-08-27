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
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ExchangeAvailability;
use App\Services\ReturnRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exchanges.
 *
 * The rule under test throughout: a replacement must be genuinely sendable —
 * same product, active, in stock — and that has to be confirmed twice, because
 * in a fashion store the last size 38 goes to whoever reaches it first, and an
 * answer given at request time is not evidence at approval time.
 */
class ExchangeTest extends TestCase
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
     * A delivered order, plus a second variant of the same product to swap for.
     *
     * @return array{0: Order, 1: OrderItem, 2: ProductVariant, 3: ProductVariant}
     */
    private function scenario(int $quantity = 1, int $replacementStock = 5): array
    {
        $product = Product::factory()->create();

        $bought = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 3,
        ]);

        $wanted = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => $replacementStock,
        ]);

        $order = Order::factory()->create([
            'user_id'      => $this->customer->id,
            'status'       => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);

        OrderAddress::factory()->for($order)->create();

        $item = OrderItem::factory()->for($order)->create([
            'product_id'         => $product->id,
            'product_variant_id' => $bought->id,
            'quantity'           => $quantity,
        ]);

        return [$order->refresh(), $item, $bought, $wanted];
    }

    // ------------------------------------------------------- Raising the request

    public function test_an_exchange_records_the_replacement_variant(): void
    {
        [$order, $item, , $wanted] = $this->scenario();

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $line = $request->items->first();

        $this->assertSame($wanted->id, $line->replacement_variant_id);
        $this->assertTrue($line->isExchange());
    }

    /**
     * Snapshotted like order items, so a variant later renamed or retired does
     * not rewrite what was agreed.
     */
    public function test_the_replacement_is_snapshotted(): void
    {
        [$order, $item, , $wanted] = $this->scenario();

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $line = $request->items->first();
        $this->assertSame($wanted->sku, $line->replacement_sku);

        // Retire the variant entirely.
        $wanted->delete();

        $line->refresh();
        $this->assertNull($line->replacement_variant_id);

        // The record of what was agreed survives.
        $this->assertSame($wanted->sku, $line->replacement_sku);
        $this->assertTrue($line->isExchange());
    }

    public function test_an_exchange_must_name_a_replacement(): void
    {
        [$order, $item] = $this->scenario();

        $this->expectException(ReturnNotAllowedException::class);

        // No replacement given: not a request anyone can act on.
        $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
        );
    }

    public function test_a_plain_return_needs_no_replacement(): void
    {
        [$order, $item] = $this->scenario();

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Return_, ReturnReason::ChangedMind,
        );

        $this->assertFalse($request->items->first()->isExchange());
    }

    // ---------------------------------------------------------- Validity rules

    public function test_a_replacement_from_another_product_is_refused(): void
    {
        [$order, $item] = $this->scenario();
        $elsewhere = ProductVariant::factory()->create(['stock_quantity' => 10]);

        $this->expectException(ReturnNotAllowedException::class);

        // Sending a different garment is a new order, not an exchange.
        $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $elsewhere->id],
        );
    }

    public function test_a_retired_replacement_is_refused(): void
    {
        [$order, $item, , $wanted] = $this->scenario();
        $wanted->update(['is_active' => false]);

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );
    }

    public function test_a_sold_out_replacement_is_refused(): void
    {
        [$order, $item, , $wanted] = $this->scenario(replacementStock: 0);

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );
    }

    public function test_a_replacement_must_cover_the_whole_quantity(): void
    {
        [$order, $item, , $wanted] = $this->scenario(quantity: 3, replacementStock: 2);

        $this->expectException(ReturnNotAllowedException::class);

        // Two on the shelf cannot replace three.
        $this->service()->request(
            $order, [$item->id => 3], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );
    }

    /**
     * The same size back is legitimate when the first arrived damaged.
     */
    public function test_the_same_variant_may_be_requested_again(): void
    {
        [$order, $item, $bought] = $this->scenario();

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::Damaged,
            $this->customer, null, [$item->id => $bought->id],
        );

        $this->assertSame($bought->id, $request->items->first()->replacement_variant_id);
    }

    // ------------------------------------------------- Re-checked at approval

    /**
     * The case that makes re-checking necessary rather than merely tidy.
     */
    public function test_a_replacement_that_sells_out_before_approval_blocks_it(): void
    {
        [$order, $item, , $wanted] = $this->scenario(replacementStock: 1);

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        // Someone else buys the last one in the meantime.
        $wanted->update(['stock_quantity' => 0]);

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->approve($request, $this->admin);
    }

    public function test_a_replacement_retired_before_approval_blocks_it(): void
    {
        [$order, $item, , $wanted] = $this->scenario();

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $wanted->update(['is_active' => false]);

        $this->expectException(ReturnNotAllowedException::class);

        $this->service()->approve($request, $this->admin);
    }

    // ----------------------------------------------------- Inventory movement

    public function test_receiving_an_exchange_sends_the_replacement_out(): void
    {
        [$order, $item, $bought, $wanted] = $this->scenario(quantity: 2);

        $boughtBefore = $bought->stock_quantity;
        $wantedBefore = $wanted->stock_quantity;

        $request = $this->service()->request(
            $order, [$item->id => 2], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $this->service()->approve($request, $this->admin);
        $this->service()->receive($request->fresh(), $this->admin);

        // The replacement leaves the shelf...
        $this->assertSame($wantedBefore - 2, $wanted->fresh()->stock_quantity);

        // ...and the returned piece does not rejoin it, because it is a swap.
        $this->assertSame($boughtBefore, $bought->fresh()->stock_quantity);
    }

    public function test_nothing_moves_until_the_parcel_arrives(): void
    {
        [$order, $item, , $wanted] = $this->scenario();

        $before = $wanted->stock_quantity;

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $this->service()->approve($request, $this->admin);

        // Approving is a promise, not a dispatch.
        $this->assertSame($before, $wanted->fresh()->stock_quantity);
    }

    /**
     * Only what came back earns a replacement.
     */
    public function test_a_line_that_did_not_arrive_sends_no_replacement(): void
    {
        [$order, $item, , $wanted] = $this->scenario(quantity: 2);

        $before = $wanted->stock_quantity;

        $request = $this->service()->request(
            $order, [$item->id => 2], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $this->service()->approve($request, $this->admin);

        $line = $request->items->first();
        $this->service()->receive($request->fresh(), $this->admin, [$line->id => 0]);

        $this->assertSame($before, $wanted->fresh()->stock_quantity);
    }

    public function test_only_the_quantity_received_is_dispatched(): void
    {
        [$order, $item, , $wanted] = $this->scenario(quantity: 3);

        $before = $wanted->stock_quantity;

        $request = $this->service()->request(
            $order, [$item->id => 3], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $this->service()->approve($request, $this->admin);

        $line = $request->items->first();
        $this->service()->receive($request->fresh(), $this->admin, [$line->id => 1]);

        // One came back, so one goes out.
        $this->assertSame($before - 1, $wanted->fresh()->stock_quantity);
    }

    /**
     * Stock is re-checked under a lock at dispatch, so an exchange cannot drive
     * a variant negative.
     */
    public function test_a_replacement_that_sells_out_before_receipt_blocks_the_receipt(): void
    {
        [$order, $item, , $wanted] = $this->scenario(replacementStock: 1);

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $this->service()->approve($request, $this->admin);

        // Sold between approval and the parcel arriving.
        $wanted->update(['stock_quantity' => 0]);

        try {
            $this->service()->receive($request->fresh(), $this->admin);
            $this->fail('Receiving should have been refused.');
        } catch (ReturnNotAllowedException) {
            // The whole receipt rolls back: no negative stock, and the request
            // stays where it was rather than being marked received.
            $this->assertSame(0, $wanted->fresh()->stock_quantity);
            $this->assertSame(ReturnStatus::Approved, $request->fresh()->status);
        }
    }

    // ------------------------------------------------------------------- HTTP

    public function test_a_customer_can_raise_an_exchange_through_the_form(): void
    {
        [$order, $item, , $wanted] = $this->scenario();

        $this->actingAs($this->customer)
            ->post(route('store.account.returns.store', ['locale' => 'en', 'order' => $order]), [
                'type'   => 'exchange',
                'reason' => 'wrong_size',
                // HTML posts strings throughout, keys included.
                'quantities'   => [(string) $item->id => '1'],
                'replacements' => [(string) $item->id => (string) $wanted->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $wanted->id,
            $order->returnRequests()->first()->items->first()->replacement_variant_id,
        );
    }

    public function test_an_exchange_without_a_replacement_is_an_error_not_a_500(): void
    {
        [$order, $item] = $this->scenario();

        $this->actingAs($this->customer)
            ->post(route('store.account.returns.store', ['locale' => 'en', 'order' => $order]), [
                'type'       => 'exchange',
                'reason'     => 'wrong_size',
                'quantities' => [$item->id => 1],
            ])
            ->assertSessionHasErrors('quantities');

        $this->assertSame(0, $order->returnRequests()->count());
    }

    public function test_staff_can_receive_an_exchange_through_the_queue(): void
    {
        [$order, $item, , $wanted] = $this->scenario();

        $before = $wanted->stock_quantity;

        $request = $this->service()->request(
            $order, [$item->id => 1], ReturnType::Exchange, ReturnReason::WrongSize,
            $this->customer, null, [$item->id => $wanted->id],
        );

        $this->service()->approve($request, $this->admin);

        $line = $request->items->first();

        $this->actingAs($this->admin)
            ->patch(route('admin.returns.decide', ['locale' => 'en', 'return' => $request]), [
                'decision' => 'receive',
                'received' => [(string) $line->id => '1'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(ReturnStatus::Received, $request->fresh()->status);
        $this->assertSame($before - 1, $wanted->fresh()->stock_quantity);
    }

    // ------------------------------------------------------------ Availability

    public function test_only_sellable_variants_of_the_same_product_are_offered(): void
    {
        [, $item, $bought, $wanted] = $this->scenario();

        $soldOut = ProductVariant::factory()->create([
            'product_id'     => $item->product_id,
            'stock_quantity' => 0,
        ]);

        $retired = ProductVariant::factory()->create([
            'product_id'     => $item->product_id,
            'stock_quantity' => 5,
            'is_active'      => false,
        ]);

        $otherProduct = ProductVariant::factory()->create(['stock_quantity' => 5]);

        $offered = app(ExchangeAvailability::class)->optionsFor($item)->pluck('id');

        $this->assertTrue($offered->contains($bought->id));
        $this->assertTrue($offered->contains($wanted->id));

        $this->assertFalse($offered->contains($soldOut->id));
        $this->assertFalse($offered->contains($retired->id));
        $this->assertFalse($offered->contains($otherProduct->id));
    }

    /**
     * The return form lists every line on the order, so asking per line would
     * be one query per garment.
     */
    public function test_the_return_form_does_not_query_per_line(): void
    {
        [$order] = $this->scenario();

        $baseline = $this->countQueriesBuildingTheForm($order);

        // Three more garments on the same order.
        for ($i = 0; $i < 3; $i++) {
            $variant = ProductVariant::factory()->create(['stock_quantity' => 4]);

            OrderItem::factory()->for($order)->create([
                'product_id'         => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity'           => 1,
            ]);
        }

        $this->assertSame($baseline, $this->countQueriesBuildingTheForm($order->refresh()));
    }

    private function countQueriesBuildingTheForm(Order $order): int
    {
        $order->load('items');

        $count = 0;
        \DB::listen(function () use (&$count): void {
            $count++;
        });

        foreach ($this->service()->returnableLines($order) as $line) {
            // Touch what the form renders.
            $line['replacements']->each(fn ($variant) => $variant->size?->name);
        }

        return $count;
    }
}
