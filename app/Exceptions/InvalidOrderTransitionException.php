<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\OrderStatus;
use RuntimeException;

/**
 * Raised when an order cannot move as asked.
 *
 * Either the transition is not one the lifecycle allows, or reinstating a
 * cancelled order would need stock that is no longer there.
 */
class InvalidOrderTransitionException extends RuntimeException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function between(OrderStatus $from, OrderStatus $to): self
    {
        return new self('transition', __('orders.errors.invalid_transition', [
            'from' => $from->label(),
            'to'   => $to->label(),
        ]));
    }

    public static function insufficientStock(string $sku): self
    {
        return new self('stock', __('orders.errors.restock_unavailable', ['sku' => $sku]));
    }
}
