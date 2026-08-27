<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How an order is paid for.
 *
 * HOOR is cash on delivery only. The enum exists so the choice is explicit on
 * every order rather than implied, and so adding a gateway later is a new case
 * rather than a schema change.
 */
enum PaymentMethod: string
{
    case CashOnDelivery = 'cash_on_delivery';

    public function label(): string
    {
        return __('orders.payment.'.$this->value);
    }

    /**
     * Whether payment is collected before the parcel is handed over.
     */
    public function isPrepaid(): bool
    {
        return false;
    }
}
