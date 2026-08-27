<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Casts\Money;
use App\Models\Order;
use App\Models\OrderAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderAddress>
 */
class OrderAddressFactory extends Factory
{
    protected $model = OrderAddress::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id'  => Order::factory(),
            'full_name' => fake()->name(),
            'phone'     => '01'.fake()->numberBetween(0, 2).fake()->numerify('########'),
            'governorate_name_en' => 'Cairo',
            'governorate_name_ar' => 'القاهرة',
            'address'      => fake()->streetAddress(),
            'shipping_fee' => Money::fromMajor(45),
        ];
    }
}
