<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Why the customer is sending it back.
 *
 * A fixed list rather than free text, because the aggregate is what makes it
 * useful: "wrong size" recurring on one product says the size chart is wrong,
 * and that only surfaces if the reasons are countable.
 *
 * The customer's own words are still captured, in `customer_note`.
 */
enum ReturnReason: string
{
    case WrongSize = 'wrong_size';
    case NotAsDescribed = 'not_as_described';
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case ChangedMind = 'changed_mind';
    case Other = 'other';

    public function label(): string
    {
        return __('returns.reason.'.$this->value);
    }

    /**
     * Whether the fault lies with us.
     *
     * Kept here so reporting can separate "we got it wrong" from "she changed
     * her mind" without every caller re-deciding which is which.
     */
    public function isOurFault(): bool
    {
        return in_array($this, [self::Damaged, self::WrongItem, self::NotAsDescribed], strict: true);
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
