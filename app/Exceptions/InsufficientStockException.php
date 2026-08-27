<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a basket cannot be fulfilled from the stock actually on hand.
 *
 * Carries the specific shortfalls rather than a bare message, so checkout can
 * tell the customer which lines are the problem and by how much — the
 * difference between "something went wrong" and "only 2 of these left".
 */
class InsufficientStockException extends RuntimeException
{
    /**
     * @param  list<array{name: string, variant: string, requested: int, available: int}>  $shortfalls
     */
    public function __construct(public readonly array $shortfalls)
    {
        parent::__construct('The order cannot be fulfilled from current stock.');
    }

    /**
     * @param  list<array{name: string, variant: string, requested: int, available: int}>  $shortfalls
     */
    public static function forLines(array $shortfalls): self
    {
        return new self($shortfalls);
    }

    /**
     * Customer-facing messages, one per affected line.
     *
     * @return list<string>
     */
    public function messages(): array
    {
        return array_map(
            static fn (array $line): string => $line['available'] === 0
                ? __('checkout.errors.line_sold_out', [
                    'name'    => $line['name'],
                    'variant' => $line['variant'],
                ])
                : __('checkout.errors.line_short', [
                    'name'      => $line['name'],
                    'variant'   => $line['variant'],
                    'available' => $line['available'],
                ]),
            $this->shortfalls,
        );
    }
}
