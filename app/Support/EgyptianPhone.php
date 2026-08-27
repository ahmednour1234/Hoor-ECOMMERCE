<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Egyptian mobile numbers, as customers actually type them.
 *
 * The same number arrives as ٠١٠١٢٣٤٥٦٧٨, +20 101 234 5678, or 0101-234-5678
 * depending on the keyboard and the habit. Tracking has to match what checkout
 * stored, so both ends normalise through here.
 */
final class EgyptianPhone
{
    /**
     * Egyptian mobiles: 010, 011, 012 or 015 followed by eight digits.
     */
    public const PATTERN = '/^01[0125][0-9]{8}$/';

    public const RULE = 'regex:'.self::PATTERN;

    /**
     * Reduce a typed number to its canonical form.
     */
    public static function normalise(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = self::toLatinDigits((string) $value);

        // Strip the spaces, dashes and brackets people use to group digits.
        $value = preg_replace('/[\s\-()]/', '', $value) ?? '';

        // +20 and 0020 are the same local 0.
        $value = preg_replace('/^(?:\+?20|0020)/', '0', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    /**
     * Arabic-Indic digits to Latin, so ٠١٠ matches 010.
     */
    public static function toLatinDigits(string $value): string
    {
        return str_replace(
            ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $value,
        );
    }

    public static function isValid(mixed $value): bool
    {
        $normalised = self::normalise($value);

        return $normalised !== null && preg_match(self::PATTERN, $normalised) === 1;
    }
}
