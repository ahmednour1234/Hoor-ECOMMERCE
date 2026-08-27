<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id'         => Product::factory(),
            'product_variant_id' => null,
            'path'               => 'products/'.fake()->unique()->uuid().'.jpg',
            'alt_en'             => fake()->words(3, true),
            'alt_ar'             => 'صورة منتج',
            'sort_order'         => fake()->numberBetween(0, 10),
            'is_primary'         => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }
}
