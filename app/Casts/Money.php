<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Stores money as an integer number of piastres (1 EGP = 100 piastres).
 *
 * Binary floats cannot represent most decimal fractions exactly, so keeping
 * money in the smallest indivisible unit makes every sum, discount and total
 * exact. The cast is deliberately transparent: models expose and accept plain
 * integers of piastres, and formatting for humans is the presentation layer's
 * job (see Money::format()).
 *
 * @implements CastsAttributes<int|null, int|null>
 */
class Money implements CastsAttributes
{
    public const SUBUNITS = 100;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException(
                "The [{$key}] attribute expects a whole number of piastres."
            );
        }

        $piastres = (int) $value;

        if ($piastres < 0) {
            throw new InvalidArgumentException("The [{$key}] attribute cannot be negative.");
        }

        return $piastres;
    }

    /**
     * Convert a major-unit amount (e.g. "249.50" EGP) into piastres.
     *
     * Rounding happens once, here, so callers never accumulate drift.
     */
    public static function fromMajor(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * self::SUBUNITS);
    }

    public static function toMajor(int $piastres): float
    {
        return $piastres / self::SUBUNITS;
    }

    /**
     * Render an amount for display in the active locale.
     */
    public static function format(?int $piastres, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $currency = config('hoor.currency');
        $symbol = $currency['symbol_'.$locale] ?? $currency['symbol_en'];

        $amount = number_format(
            self::toMajor((int) $piastres),
            $currency['decimals'],
        );

        return $locale === 'ar' ? "{$amount} {$symbol}" : "{$symbol} {$amount}";
    }
}
