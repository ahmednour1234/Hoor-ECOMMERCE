<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The back-office order screens.
 */
class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    /**
     * A complete order: address, one line, and stock behind it.
     */
    private function order(OrderStatus $status = OrderStatus::Pending, array $attributes = []): Order
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

        $order = Order::factory()->status($status)->create($attributes);

        OrderAddress::factory()->for($order)->create();

        OrderItem::factory()->for($order)->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => 2,
        ]);

        return $order;
    }

    // --------------------------------------------------------- Authorization

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.orders.index', ['locale' => 'en']))
            ->assertRedirect();
    }

    public function test_a_customer_cannot_reach_the_order_screens(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.orders.index', ['locale' => 'en']))
            ->assertForbidden();
    }

    // ----------------------------------------------------------------- Index

    public function test_the_index_lists_orders(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en']))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee($order->address->full_name);
    }

    public function test_each_status_tab_carries_its_own_count(): void
    {
        $this->order(OrderStatus::Pending);
        $this->order(OrderStatus::Pending);
        $this->order(OrderStatus::Delivered);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en']));

        $counts = $response->viewData('counts');

        $this->assertSame(3, $counts['all']);
        $this->assertSame(2, $counts['pending']);
        $this->assertSame(1, $counts['delivered']);

        // Every status is present, so a zero tab is a real zero.
        $this->assertSame(0, $counts['shipped']);
        $this->assertCount(count(OrderStatus::cases()) + 1, $counts);
    }

    public function test_the_status_filter_narrows_the_list(): void
    {
        $pending = $this->order(OrderStatus::Pending);
        $delivered = $this->order(OrderStatus::Delivered);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en', 'status' => 'delivered']))
            ->assertOk()
            ->assertSee($delivered->number)
            ->assertDontSee($pending->number);
    }

    public function test_an_unknown_status_falls_back_to_all_orders_rather_than_erroring(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en', 'status' => 'nonsense']))
            ->assertOk()
            ->assertSee($order->number);
    }

    public function test_orders_can_be_searched_by_number_and_by_customer_phone(): void
    {
        $wanted = $this->order();
        $other = $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en', 'search' => $wanted->number]))
            ->assertOk()
            ->assertSee($wanted->number)
            ->assertDontSee($other->number);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en', 'search' => $wanted->address->phone]))
            ->assertOk()
            ->assertSee($wanted->number);
    }

    /**
     * The listing renders a customer name and an item count per row, both of
     * which live on relations. Without eager loading this grows with the page.
     */
    public function test_the_listing_query_count_does_not_grow_with_the_number_of_orders(): void
    {
        $this->order();

        $baseline = $this->countQueriesForIndex();

        for ($i = 0; $i < 6; $i++) {
            $this->order();
        }

        $this->assertSame($baseline, $this->countQueriesForIndex());
    }

    private function countQueriesForIndex(): int
    {
        $count = 0;
        \DB::listen(function () use (&$count): void {
            $count++;
        });

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index', ['locale' => 'en']))
            ->assertOk();

        \DB::flushQueryLog();

        return $count;
    }

    // ---------------------------------------------------------------- Detail

    public function test_the_detail_page_shows_everything_the_staff_need(): void
    {
        $order = $this->order();
        $item = $order->items->first();
        $address = $order->address;

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee($address->full_name)
            ->assertSee($address->phone)
            ->assertSee($address->address)
            ->assertSee($item->sku)
            ->assertSee($item->product_name)
            ->assertSee(\App\Casts\Money::format($order->subtotal))
            ->assertSee(\App\Casts\Money::format($order->shipping))
            ->assertSee(\App\Casts\Money::format($order->total))
            ->assertSee(__('orders.admin.history'));
    }

    public function test_the_detail_page_offers_only_the_transitions_the_graph_permits(): void
    {
        $order = $this->order(OrderStatus::Pending);

        $transitions = $this->actingAs($this->admin)
            ->get(route('admin.orders.show', ['locale' => 'en', 'order' => $order]))
            ->viewData('transitions');

        $this->assertSame(['confirmed', 'cancelled'], array_keys($transitions));
    }

    public function test_a_final_order_offers_no_transitions(): void
    {
        $order = $this->order(OrderStatus::Cancelled);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', ['locale' => 'en', 'order' => $order]))
            ->assertOk()
            ->assertSee(__('orders.admin.no_transitions'));
    }

    public function test_the_detail_page_renders_in_arabic_with_rtl(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', ['locale' => 'ar', 'order' => $order]))
            ->assertOk()
            ->assertSee('dir="rtl"', escape: false)
            ->assertSee(__('orders.admin.products', locale: 'ar'));
    }

    // ------------------------------------------------------- Status changes

    public function test_an_admin_can_move_an_order_forward(): void
    {
        $order = $this->order(OrderStatus::Pending);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', ['locale' => 'en', 'order' => $order]), [
                'status' => 'confirmed',
                'note'   => 'Confirmed on the phone.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);

        $entry = $order->statusHistory()->first();
        $this->assertSame($this->admin->id, $entry->user_id);
        $this->assertSame('Confirmed on the phone.', $entry->note);
    }

    public function test_an_invalid_transition_is_reported_as_a_field_error(): void
    {
        $order = $this->order(OrderStatus::Pending);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', ['locale' => 'en', 'order' => $order]), [
                'status' => 'delivered',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, $order->statusHistory()->count());
    }

    public function test_a_status_outside_the_enum_is_rejected(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', ['locale' => 'en', 'order' => $order]), [
                'status' => 'refunded_via_stripe',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_cancelling_through_the_admin_restores_stock(): void
    {
        $order = $this->order(OrderStatus::Pending);
        $variant = $order->items->first()->variant;

        $before = $variant->stock_quantity;

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.status', ['locale' => 'en', 'order' => $order]), [
                'status' => 'cancelled',
            ])
            ->assertRedirect();

        $this->assertSame($before + 2, $variant->fresh()->stock_quantity);
    }

    public function test_a_customer_cannot_change_an_order_status(): void
    {
        $order = $this->order();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->patch(route('admin.orders.status', ['locale' => 'en', 'order' => $order]), [
                'status' => 'confirmed',
            ])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }
}
