<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Casts\Money;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $unitPrice = Money::fromMajor(fake()->numberBetween(400, 1600));
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id'        => Order::factory(),
            'product_name_en' => fake()->words(3, true),
            'product_name_ar' => 'منتج اختبار',
            'sku'             => strtoupper(fake()->unique()->bothify('HOOR-####-???-##')),
            'color_name_en'   => 'Indigo',
            'color_name_ar'   => 'نيلي',
            'size_name_en'    => 'M',
            'size_name_ar'    => 'M',
            'unit_price'                 => $unitPrice,
            'unit_price_before_discount' => $unitPrice,
            'quantity'                   => $quantity,
            'line_total'                 => $unitPrice * $quantity,
        ];
    }
}
