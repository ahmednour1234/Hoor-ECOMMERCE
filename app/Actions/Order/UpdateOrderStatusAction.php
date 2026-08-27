<?php

declare(strict_types=1);

namespace App\Actions\Order;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;

/**
 * Moves an order to a new status.
 *
 * The only place an order's status changes. Three things happen together, in
 * one transaction:
 *
 *   1. The transition is checked against OrderStatus::canTransitionTo(), so an
 *      order cannot jump from pending straight to delivered, or move on from a
 *      final state.
 *
 *   2. Stock is released or re-taken when the transition crosses that boundary.
 *      Cancelling or returning an order puts its units back on the shelf;
 *      reinstating one takes them again, and refuses if they are no longer
 *      there.
 *
 *   3. A history entry is appended. History is never edited or deleted — the
 *      record of how an order progressed has to stay trustworthy, and an
 *      audit trail that can be rewritten is not one.
 */
class UpdateOrderStatusAction
{
    public function __construct(private readonly CouponService $coupons)
    {
    }

    /**
     * @throws InvalidOrderTransitionException
     */
    public function execute(
        Order $order,
        OrderStatus $to,
        ?User $actor = null,
        ?string $note = null,
    ): Order {
        $from = $order->status;

        if ($from === $to) {
            return $order;
        }

        if (! $from->canTransitionTo($to)) {
            throw InvalidOrderTransitionException::between($from, $to);
        }

        return DB::transaction(function () use ($order, $from, $to, $actor, $note): Order {
            // Stock moves before the status is written, so a failure to
            // reinstate units leaves the order where it was rather than in a
            // state its stock does not back.
            $this->reconcileStock($order, $from, $to);

            // The same crossing decides the coupon: an order that was
            // cancelled never gave the customer her discount, so she keeps the
            // entitlement.
            $this->reconcileCoupon($order, $from, $to);

            $order->update(array_merge(
                ['status' => $to],
                $this->timestampsFor($to),
            ));

            $order->statusHistory()->create([
                'from_status' => $from,
                'to_status'   => $to,
                'user_id'     => $actor?->id,
                'note'        => $note,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Release or re-spend the order's coupon across the same boundary.
     *
     * Delegated to CouponService, which owns the redemption record and the
     * counter — duplicating either here would give the shop two answers to
     * "how many times has this code been used".
     */
    private function reconcileCoupon(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        if ($order->coupon_id === null || $from->holdsStock() === $to->holdsStock()) {
            return;
        }

        $to->holdsStock()
            ? $this->coupons->redeem($order->loadMissing('address'))
            : $this->coupons->release($order);
    }

    /**
     * Put stock back, or take it again, when a transition crosses the boundary
     * between holding stock and not.
     *
     * Only the crossing matters: moving from shipped to delivered changes
     * nothing about what is on the shelf.
     */
    private function reconcileStock(Order $order, OrderStatus $from, OrderStatus $to): void
    {
        $held = $from->holdsStock();
        $willHold = $to->holdsStock();

        if ($held === $willHold) {
            return;
        }

        $willHold
            ? $this->takeStock($order)
            : $this->releaseStock($order);
    }

    /**
     * Return an order's units to the catalog.
     *
     * Locked in ascending id order for the same reason as order creation: a
     * consistent lock order makes deadlocks between concurrent operations
     * impossible.
     *
     * Lines whose variant has since been deleted are skipped — there is
     * nothing to restock, and the order's own snapshot is unaffected.
     */
    private function releaseStock(Order $order): void
    {
        foreach ($this->lockVariantsFor($order) as $variantId => $quantity) {
            ProductVariant::query()
                ->whereKey($variantId)
                ->increment('stock_quantity', $quantity);
        }
    }

    /**
     * Re-take stock for an order being reinstated.
     *
     * No transition in the current graph reaches here: cancelled and returned
     * are terminal, so nothing moves from not-holding back to holding. This is
     * the guard for the day someone adds such an edge — reviving an order must
     * not drive stock negative or promise what cannot be shipped. A test pins
     * the property, so adding the edge without the stock story fails loudly.
     *
     * @throws InvalidOrderTransitionException
     */
    private function takeStock(Order $order): void
    {
        foreach ($this->lockVariantsFor($order) as $variantId => $quantity) {
            $affected = ProductVariant::query()
                ->whereKey($variantId)
                ->where('stock_quantity', '>=', $quantity)
                ->decrement('stock_quantity', $quantity);

            if ($affected === 0) {
                throw InvalidOrderTransitionException::insufficientStock(
                    ProductVariant::query()->find($variantId)?->sku ?? (string) $variantId,
                );
            }
        }
    }

    /**
     * Quantities per variant, with the rows locked.
     *
     * Quantities are summed per variant in case an order somehow carries the
     * same variant on two lines, so the restock is right either way.
     *
     * @return array<int, int>  variant id => quantity
     */
    private function lockVariantsFor(Order $order): array
    {
        $quantities = $order->items()
            ->whereNotNull('product_variant_id')
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn ($lines): int => (int) $lines->sum('quantity'))
            ->sortKeys()
            ->all();

        if ($quantities !== []) {
            ProductVariant::query()
                ->whereIn('id', array_keys($quantities))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }

        return $quantities;
    }

    /**
     * Stamp the milestone timestamps a status implies.
     *
     * @return array<string, \Illuminate\Support\Carbon>
     */
    private function timestampsFor(OrderStatus $to): array
    {
        return match ($to) {
            OrderStatus::Confirmed => ['confirmed_at' => now()],
            OrderStatus::Delivered => ['delivered_at' => now()],
            OrderStatus::Cancelled => ['cancelled_at' => now()],
            default                => [],
        };
    }
}
