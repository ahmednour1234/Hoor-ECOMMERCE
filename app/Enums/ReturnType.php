<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Whether the customer wants their money back or a different piece.
 */
enum ReturnType: string
{
    case Return_ = 'return';
    case Exchange = 'exchange';

    public function label(): string
    {
        return __('returns.type.'.$this->value);
    }

    /**
     * Whether approving this brings the units back onto the shelf.
     *
     * A return does; an exchange sends a replacement out, so the stock story
     * is handled when that replacement order is placed rather than here.
     */
    public function restocks(): bool
    {
        return $this === self::Return_;
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
