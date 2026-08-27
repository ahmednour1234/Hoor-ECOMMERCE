<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when the basket changed between the customer reviewing it and
 * confirming the order.
 *
 * Distinct from InsufficientStockException: nothing here is necessarily
 * unfulfillable, but the order is no longer the one the customer agreed to, so
 * it must be shown to them again before any money is committed.
 */
class CartChangedException extends RuntimeException
{
    /**
     * @param  list<string>  $notices  Customer-facing explanations.
     */
    public function __construct(public readonly array $notices)
    {
        parent::__construct('The basket changed before the order was placed.');
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return $this->notices;
    }
}
