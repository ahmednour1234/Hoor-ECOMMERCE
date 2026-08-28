<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Support\Cart\Cart;
use App\Support\EgyptianPhone;
use Illuminate\Support\Facades\DB;

/**
 * Validates discount codes and works out what they are worth.
 *
 * The one place a coupon is judged. Cart and checkout both call resolve(), so
 * the discount a customer is quoted in her basket is computed by exactly the
 * same code that computes the discount she is charged — there is no second
 * implementation to drift.
 *
 * Everything happens server-side against the stored coupon and the server's own
 * view of the cart. Nothing about a discount is ever read from the request:
 * the customer submits a code, never an amount.
 *
 * An invalid code yields a zero discount with a reason rather than throwing, so
 * a stale code pasted from an old email never blocks a customer from ordering —
 * she simply pays full price and is told why.
 */
class CouponService
{
    /**
     * The shape every caller reads.
     *
     * Unchanged from when this class was a placeholder, which is why filling it
     * in touched no caller.
     *
     * @return array{id: int|null, code: string|null, discount: int, valid: bool, reason: string|null}
     */
    public function resolve(
        ?string $code,
        Cart $cart,
        ?string $phone = null,
        ?int $userId = null,
    ): array {
        $code = $this->normalise($code);

        if ($code === null) {
            return $this->none();
        }

        $coupon = Coupon::query()->code($code)->first();

        if ($coupon === null) {
            return $this->none($code, 'not_found');
        }

        $reason = $this->rejectionFor($coupon, $cart->subtotal(), $phone, $userId);

        if ($reason !== null) {
            return $this->none($code, $reason);
        }

        $discount = $coupon->discountFor($cart->subtotal());

        // A coupon worth nothing against this basket is not an error, but it is
        // not a valid discount either — telling her it applied while taking
        // nothing off would be worse than saying it does not.
        if ($discount < 1) {
            return $this->none($code, 'no_value');
        }

        return [
            'id'       => $coupon->id,
            'code'     => $coupon->code,
            'discount' => $discount,
            'valid'    => true,
            'reason'   => null,
        ];
    }

    /**
     * Why this coupon cannot be used, or null if it can.
     *
     * Ordered from the general to the personal, so a customer is told the most
     * useful thing first: an expired code is expired for everyone, and saying
     * "you have already used this" about a dead campaign would be confusing.
     */
    private function rejectionFor(Coupon $coupon, int $subtotal, ?string $phone, ?int $userId): ?string
    {
        return match (true) {
            ! $coupon->is_active                => 'inactive',
            ! $coupon->hasStarted()             => 'not_started',
            $coupon->hasExpired()               => 'expired',
            $coupon->isExhausted()              => 'exhausted',
            ! $coupon->coversMinimum($subtotal) => 'below_minimum',
            $this->customerIsDone($coupon, $phone, $userId) => 'already_used',
            default                             => null,
        };
    }

    /**
     * Whether this customer has already had all the uses she is allowed.
     *
     * Counted from the redemption history rather than a flag, so it is right
     * for a guest who has never had an account to carry one.
     */
    private function customerIsDone(Coupon $coupon, ?string $phone, ?int $userId): bool
    {
        if ($coupon->per_customer_limit === null) {
            return false;
        }

        $phone = EgyptianPhone::normalise($phone);

        // Nobody to check against — an anonymous quote in the cart, before
        // she has entered a phone number.
        if ($phone === null && $userId === null) {
            return false;
        }

        $used = CouponRedemption::query()
            ->where('coupon_id', $coupon->id)
            ->forCustomer($phone, $userId)
            ->count();

        return $used >= $coupon->per_customer_limit;
    }

    /**
     * Whether this customer has already had all the uses she is allowed.
     *
     * Public because the checkout banner needs the same answer: an offer shown
     * to someone who has already taken it is a promise that breaks the moment
     * she acts on it. Asking here rather than duplicating the rule means the
     * banner and the discount cannot disagree.
     */
    public function customerHasUsed(Coupon $coupon, ?string $phone, ?int $userId = null): bool
    {
        return $this->customerIsDone($coupon, $phone, $userId);
    }

    /**
     * Whether a code would currently be accepted.
     */
    public function isValid(?string $code, Cart $cart, ?string $phone = null, ?int $userId = null): bool
    {
        return $this->resolve($code, $cart, $phone, $userId)['valid'];
    }

    /**
     * Record that a code was used, when an order is placed.
     *
     * Called inside the order transaction, so a failed order leaves no
     * redemption behind and the customer keeps her use.
     *
     * The counter is incremented with an atomic expression rather than a
     * read-modify-write, so two customers redeeming the last use of a code at
     * the same moment cannot both see the same starting count.
     */
    public function redeem(Order $order): ?CouponRedemption
    {
        if ($order->coupon_id === null || $order->discount < 1) {
            return null;
        }

        $redemption = CouponRedemption::create([
            'coupon_id' => $order->coupon_id,
            'order_id'  => $order->id,
            'user_id'   => $order->user_id,
            'phone'     => EgyptianPhone::normalise($order->address?->phone),
            'discount'  => $order->discount,
        ]);

        Coupon::query()->whereKey($order->coupon_id)->increment('used_count');

        return $redemption;
    }

    /**
     * Give a use back when an order is cancelled or returned.
     *
     * A code spent on an order that never happened should not be spent: the
     * customer did not receive the discount, so she keeps the entitlement.
     */
    public function release(Order $order): void
    {
        if ($order->coupon_id === null) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $deleted = CouponRedemption::query()
                ->where('coupon_id', $order->coupon_id)
                ->where('order_id', $order->id)
                ->delete();

            if ($deleted > 0) {
                // Floored at zero: a counter that has drifted below the real
                // count must not go negative and start reporting unlimited use.
                Coupon::query()
                    ->whereKey($order->coupon_id)
                    ->where('used_count', '>=', $deleted)
                    ->decrement('used_count', $deleted);
            }
        });
    }

    /**
     * Codes are stored and compared upper-case, so casing never matters to a
     * customer typing one in.
     */
    private function normalise(?string $code): ?string
    {
        $code = Coupon::normaliseCode((string) $code);

        return $code === '' ? null : $code;
    }

    /**
     * @return array{id: int|null, code: string|null, discount: int, valid: bool, reason: string|null}
     */
    private function none(?string $code = null, ?string $reason = null): array
    {
        return [
            'id'       => null,
            'code'     => null,
            'discount' => 0,
            'valid'    => false,
            'reason'   => $reason,
        ];
    }
}
