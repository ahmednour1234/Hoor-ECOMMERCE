<?php

declare(strict_types=1);

namespace App\Support\Cart;

use App\Casts\Money;
use Illuminate\Support\Collection;

/**
 * A hydrated cart: the lines a customer currently holds, plus the totals
 * derived from them.
 *
 * Every figure here is computed from live database prices at read time. Nothing
 * is carried over from the session, so a price change in the admin is reflected
 * on the customer's next page view rather than being frozen at add time.
 *
 * Shipping and coupon discounts are deliberately absent: shipping depends on the
 * governorate chosen at checkout, and coupons arrive with their own module. The
 * cart reports its subtotal and lets checkout compose the rest.
 */
final readonly class Cart
{
    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  list<string>  $notices  Messages about changes made during hydration.
     */
    public function __construct(
        public Collection $lines,
        public array $notices = [],
    ) {
    }

    public static function empty(): self
    {
        return new self(collect());
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->lines->isNotEmpty();
    }

    /**
     * Distinct products in the cart.
     */
    public function count(): int
    {
        return $this->lines->count();
    }

    /**
     * Total number of items, used for the header badge.
     */
    public function totalQuantity(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line): int => $line->quantity);
    }

    /**
     * Sum of every line, in piastres.
     */
    public function subtotal(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line): int => $line->lineTotal());
    }

    /**
     * What the cart would cost without any product discounts.
     */
    public function subtotalBeforeDiscount(): int
    {
        return (int) $this->lines->sum(fn (CartLine $line): int => $line->lineTotalBeforeDiscount());
    }

    /**
     * Total saved through product sale prices.
     */
    public function savings(): int
    {
        return max(0, $this->subtotalBeforeDiscount() - $this->subtotal());
    }

    public function hasSavings(): bool
    {
        return $this->savings() > 0;
    }

    /**
     * Lines that can no longer be fulfilled at the quantity requested.
     *
     * @return Collection<int, CartLine>
     */
    public function unavailableLines(): Collection
    {
        return $this->lines->reject(fn (CartLine $line): bool => $line->isAvailable())->values();
    }

    /**
     * Whether every line can still be bought as it stands.
     *
     * Checkout refuses to proceed while this is false, and the cart page
     * explains which lines are the problem.
     */
    public function isCheckoutReady(): bool
    {
        return $this->isNotEmpty() && $this->unavailableLines()->isEmpty();
    }

    public function hasNotices(): bool
    {
        return $this->notices !== [];
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal());
    }

    public function formattedSavings(): string
    {
        return Money::format($this->savings());
    }
}
