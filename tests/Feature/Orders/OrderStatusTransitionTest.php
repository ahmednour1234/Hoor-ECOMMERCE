<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Actions\Order\UpdateOrderStatusAction;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rules that govern how an order moves between statuses.
 *
 * Two things must hold no matter which route a transition arrives by: stock
 * always reflects which orders are live, and the history is only ever appended
 * to.
 */
class OrderStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function action(): UpdateOrderStatusAction
    {
        return app(UpdateOrderStatusAction::class);
    }

    /**
     * An order holding `$quantity` of a variant that has `$stock` left on the shelf.
     *
     * @return array{0: Order, 1: ProductVariant}
     */
    private function orderHolding(int $quantity = 2, int $stock = 5, OrderStatus $status = OrderStatus::Pending): array
    {
        $variant = ProductVariant::factory()->create(['stock_quantity' => $stock]);

        $order = Order::factory()->status($status)->create();

        OrderItem::factory()->for($order)->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => $quantity,
        ]);

        return [$order, $variant];
    }

    public function test_a_permitted_transition_moves_the_order_and_records_history(): void
    {
        [$order] = $this->orderHolding();
        $actor = User::factory()->create();

        $this->action()->execute($order, OrderStatus::Confirmed, $actor, 'Reached by phone.');

        $this->assertSame(OrderStatus::Confirmed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->confirmed_at);

        $entry = $order->statusHistory()->first();
        $this->assertSame(OrderStatus::Pending, $entry->from_status);
        $this->assertSame(OrderStatus::Confirmed, $entry->to_status);
        $this->assertSame($actor->id, $entry->user_id);
        $this->assertSame('Reached by phone.', $entry->note);
    }

    public function test_a_transition_the_graph_forbids_is_rejected(): void
    {
        [$order] = $this->orderHolding();

        $this->expectException(InvalidOrderTransitionException::class);

        // Pending goes to confirmed or cancelled — never straight to delivered.
        $this->action()->execute($order, OrderStatus::Delivered);
    }

    public function test_a_final_status_cannot_be_moved_on_from(): void
    {
        [$order] = $this->orderHolding(status: OrderStatus::Delivered);

        $this->expectException(InvalidOrderTransitionException::class);

        $this->action()->execute($order, OrderStatus::Confirmed);
    }

    public function test_a_rejected_transition_leaves_the_order_and_its_history_untouched(): void
    {
        [$order] = $this->orderHolding();

        try {
            $this->action()->execute($order, OrderStatus::Delivered);
        } catch (InvalidOrderTransitionException) {
            // expected
        }

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(0, $order->statusHistory()->count());
    }

    public function test_transitioning_to_the_same_status_is_a_no_op(): void
    {
        [$order] = $this->orderHolding();

        $this->action()->execute($order, OrderStatus::Pending);

        $this->assertSame(0, $order->statusHistory()->count());
    }

    // ------------------------------------------------------------------ Stock

    public function test_cancelling_returns_the_units_to_the_shelf(): void
    {
        [$order, $variant] = $this->orderHolding(quantity: 2, stock: 5);

        $this->action()->execute($order, OrderStatus::Cancelled);

        $this->assertSame(7, $variant->fresh()->stock_quantity);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    public function test_stock_is_only_released_once_even_across_several_transitions(): void
    {
        [$order, $variant] = $this->orderHolding(quantity: 2, stock: 5);

        // Confirmed and preparing both hold stock: crossing between them must
        // not touch the shelf.
        $this->action()->execute($order, OrderStatus::Confirmed);
        $this->action()->execute($order, OrderStatus::Preparing);

        $this->assertSame(5, $variant->fresh()->stock_quantity);

        $this->action()->execute($order, OrderStatus::Cancelled);

        $this->assertSame(7, $variant->fresh()->stock_quantity);
    }

    /**
     * Cancelled and returned are terminal: an order is never resurrected.
     *
     * A customer who changes their mind places a new order, which reprices
     * against the current catalog and takes stock through the ordinary
     * checkout path. Reviving the old one would promise yesterday's prices
     * against today's shelf.
     */
    public function test_a_cancelled_order_cannot_be_reinstated(): void
    {
        [$order, $variant] = $this->orderHolding(quantity: 2, stock: 5, status: OrderStatus::Cancelled);

        foreach (OrderStatus::cases() as $target) {
            if ($target === OrderStatus::Cancelled) {
                continue;
            }

            try {
                $this->action()->execute($order, $target);
                $this->fail("Cancelled should not move to {$target->value}.");
            } catch (InvalidOrderTransitionException) {
                // expected
            }
        }

        // Nothing was applied along the way.
        $this->assertSame(5, $variant->fresh()->stock_quantity);
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(0, $order->statusHistory()->count());
    }

    public function test_a_returned_order_is_terminal_too(): void
    {
        [$order] = $this->orderHolding(status: OrderStatus::Returned);

        $this->assertSame([], OrderStatus::Returned->nextStates());
        $this->assertTrue($order->status->isFinal());
    }

    public function test_a_returned_order_puts_its_units_back(): void
    {
        [$order, $variant] = $this->orderHolding(quantity: 3, stock: 2, status: OrderStatus::DeliveryFailed);

        $this->action()->execute($order, OrderStatus::Returned);

        $this->assertSame(5, $variant->fresh()->stock_quantity);
    }

    /**
     * Guards the reinstatement path.
     *
     * If someone later adds an edge back from a non-holding status to a
     * holding one, stock has to be re-taken and may not be there — and this
     * test failing is how they find out that story needs writing.
     */
    public function test_no_transition_moves_an_order_back_into_holding_stock(): void
    {
        $edges = [];

        foreach (OrderStatus::cases() as $from) {
            foreach ($from->nextStates() as $to) {
                if (! $from->holdsStock() && $to->holdsStock()) {
                    $edges[] = $from->value.' -> '.$to->value;
                }
            }
        }

        $this->assertSame([], $edges, 'A reinstating edge exists; UpdateOrderStatusAction::takeStock() is now live and needs test coverage.');
    }

    // ---------------------------------------------------------------- History

    public function test_history_accumulates_rather_than_being_replaced(): void
    {
        [$order] = $this->orderHolding();

        $this->action()->execute($order, OrderStatus::Confirmed);
        $this->action()->execute($order, OrderStatus::Preparing);
        $this->action()->execute($order, OrderStatus::ReadyForShipping);

        $trail = $order->fresh()->statusHistory
            ->map(fn ($e) => $e->from_status->value.'>'.$e->to_status->value)
            ->all();

        $this->assertSame([
            'pending>confirmed',
            'confirmed>preparing',
            'preparing>ready_for_shipping',
        ], $trail);
    }

    public function test_history_records_no_actor_for_a_system_transition(): void
    {
        [$order] = $this->orderHolding();

        $this->action()->execute($order, OrderStatus::Confirmed);

        $entry = $order->statusHistory()->first();

        $this->assertNull($entry->user_id);
        $this->assertSame(__('orders.history.system'), $entry->actorName());
    }
}
