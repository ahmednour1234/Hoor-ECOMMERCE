<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id'          => Product::factory(),
            'color_id'            => Color::factory(),
            'size_id'             => Size::factory(),
            'sku'                 => strtoupper(fake()->unique()->bothify('HOOR-####-???-##')),
            'stock_quantity'      => fake()->numberBetween(0, 40),
            'low_stock_threshold' => 3,
            'price'               => null,
            'sale_price'          => null,
            'is_active'           => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => ['stock_quantity' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock_quantity' => max(1, (int) ($attributes['low_stock_threshold'] ?? 3)),
        ]);
    }

    public function inStock(int $quantity = 25): static
    {
        return $this->state(fn (): array => ['stock_quantity' => $quantity]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
