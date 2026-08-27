<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Casts\Money;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = Money::fromMajor(fake()->numberBetween(500, 3000));
        $shipping = Money::fromMajor(fake()->numberBetween(40, 95));

        return [
            'number'         => sprintf('HOOR-%d-%s', now()->year, fake()->unique()->numerify('######')),
            'user_id'        => null,
            'status'         => OrderStatus::Pending,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'subtotal'       => $subtotal,
            'discount'       => 0,
            'shipping'       => $shipping,
            'total'          => $subtotal + $shipping,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    /**
     * An order that has released its stock, so it is not a sale.
     */
    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status'       => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'status'       => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }
}
