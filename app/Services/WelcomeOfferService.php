<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use App\Support\Cart\Cart;

/**
 * The "sign in and save 5%" offer shown at checkout.
 *
 * The banner is only ever shown when the discount is genuinely available to
 * the person reading it. An offer that turns out not to apply is worse than no
 * offer: she signs in expecting a saving, and the total does not move.
 *
 * So every claim the banner makes is read from the coupon itself rather than
 * written into the copy. Change the campaign in the admin — a different
 * percentage, a cap, an end date — and the banner follows, or disappears.
 */
class WelcomeOfferService
{
    public function __construct(private readonly CouponService $coupons)
    {
    }

    /**
     * The campaign behind the offer, if it is live.
     */
    public function coupon(): ?Coupon
    {
        $code = (string) config('hoor.welcome_offer.code');

        if ($code === '') {
            return null;
        }

        $coupon = Coupon::query()->code($code)->first();

        // isLive() covers active, started, not expired and not exhausted, so
        // an expired campaign takes its own banner down.
        return $coupon?->isLive() ? $coupon : null;
    }

    /**
     * Whether to offer the discount to this visitor.
     *
     * Signed-in customers are not shown it: for them it is either already
     * applied or already spent, and either way the banner would be asking them
     * to do something they have done.
     */
    public function isAvailableTo(?User $user, ?string $phone = null): bool
    {
        if ($user !== null) {
            return false;
        }

        $coupon = $this->coupon();

        if ($coupon === null) {
            return false;
        }

        /*
         * A guest who has ordered before on this phone number has already had
         * her welcome discount, so promising it again would be a lie she only
         * discovers after signing in.
         *
         * Asked of CouponService, which owns the per-customer rule, so the
         * banner and the discount cannot disagree. Only "already used"
         * disqualifies: a basket below a minimum spend is a reason to keep
         * shopping, not a reason to hide the offer.
         */
        if (blank($phone)) {
            return true;
        }

        return ! $this->coupons->customerHasUsed($coupon, $phone);
    }

    /**
     * What the discount is worth against this basket, for the banner.
     *
     * Shown so she can see the actual figure rather than a percentage she has
     * to compute — and it is the same figure checkout will apply, because it
     * comes from the same method.
     */
    public function discountFor(Cart $cart): int
    {
        $coupon = $this->coupon();

        return $coupon?->discountFor($cart->subtotal()) ?? 0;
    }

    /**
     * The code to apply once she has signed in.
     */
    public function code(): ?string
    {
        return $this->coupon()?->code;
    }

    /**
     * The percentage the banner announces, read from the coupon.
     */
    public function percentage(): ?int
    {
        $coupon = $this->coupon();

        return $coupon?->type === \App\Enums\CouponType::Percentage
            ? $coupon->value
            : null;
    }
}
