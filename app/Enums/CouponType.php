<?php

declare(strict_types=1);

namespace App\Enums;

use App\Casts\Money;

/**
 * How a coupon's value is read.
 *
 * The two kinds differ in more than arithmetic: a percentage needs a ceiling
 * and a fixed amount does not, and only a percentage can be worth more on a
 * large basket than the campaign intended.
 */
enum CouponType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return __('coupons.type.'.$this->value);
    }

    /**
     * Whether a maximum discount is meaningful for this kind.
     *
     * A fixed coupon is already its own ceiling.
     */
    public function supportsMaxDiscount(): bool
    {
        return $this === self::Percentage;
    }

    /**
     * What this coupon takes off a given subtotal, in piastres.
     *
     * Rounding is deliberate: a percentage of an odd subtotal is rarely a whole
     * piastre, and rounding down keeps the shop from giving away a piastre it
     * did not intend. The caller still clamps the result to the subtotal.
     */
    public function discountFor(int $subtotal, int $value, ?int $maxDiscount = null): int
    {
        $discount = match ($this) {
            self::Fixed      => $value,
            self::Percentage => (int) floor($subtotal * $value / 100),
        };

        if ($this->supportsMaxDiscount() && $maxDiscount !== null) {
            $discount = min($discount, $maxDiscount);
        }

        // Never more than the goods are worth, and never negative.
        return max(0, min($discount, $subtotal));
    }

    /**
     * The value as an admin reads it: "50 EGP off" or "20%".
     */
    public function formatValue(int $value): string
    {
        return $this === self::Percentage
            ? $value.'%'
            : Money::format($value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $carry, self $case): array => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
