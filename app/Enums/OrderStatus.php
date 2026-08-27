<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The lifecycle of a HOOR order.
 *
 * Cash on delivery shapes this: an order is not paid when it is placed, so the
 * flow runs from confirmation through fulfilment to the moment cash changes
 * hands at the door. `delivery_failed` exists because a courier returning with
 * the parcel is a routine outcome, not an error.
 */
enum OrderStatus: string
{
    case Pending          = 'pending';
    case Confirmed        = 'confirmed';
    case Preparing        = 'preparing';
    case ReadyForShipping = 'ready_for_shipping';
    case Shipped          = 'shipped';
    case OutForDelivery   = 'out_for_delivery';
    case Delivered        = 'delivered';
    case Cancelled        = 'cancelled';
    case DeliveryFailed   = 'delivery_failed';
    case Returned         = 'returned';

    public function label(): string
    {
        return __('orders.status.'.$this->value);
    }

    /**
     * Statuses an order can move to from here.
     *
     * Encoded as a graph rather than a straight line because real fulfilment
     * branches: a failed delivery can be retried or written off, and an order
     * can be cancelled at any point before it ships.
     *
     * @return list<self>
     */
    public function nextStates(): array
    {
        return match ($this) {
            self::Pending          => [self::Confirmed, self::Cancelled],
            self::Confirmed        => [self::Preparing, self::Cancelled],
            self::Preparing        => [self::ReadyForShipping, self::Cancelled],
            self::ReadyForShipping => [self::Shipped, self::Cancelled],
            self::Shipped          => [self::OutForDelivery, self::DeliveryFailed],
            self::OutForDelivery   => [self::Delivered, self::DeliveryFailed],
            self::DeliveryFailed   => [self::OutForDelivery, self::Returned, self::Cancelled],
            self::Delivered        => [self::Returned],
            self::Cancelled, self::Returned => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->nextStates(), strict: true);
    }

    /**
     * Whether the order has finished its journey, successfully or not.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Returned], strict: true);
    }

    /**
     * Whether stock is still committed to this order.
     *
     * Cancelling or returning releases the units back to the catalog;
     * everything else keeps them held.
     */
    public function holdsStock(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Returned], strict: true);
    }

    /**
     * Whether the customer can still cancel this themselves.
     */
    public function isCustomerCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], strict: true);
    }

    /**
     * Badge variant used by the shared <x-ui.badge> component.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Delivered                     => 'success',
            self::Cancelled, self::Returned     => 'danger',
            self::DeliveryFailed                => 'warning',
            self::Shipped, self::OutForDelivery => 'denim',
            default                             => 'neutral',
        };
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
