<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Casts\Money;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nameEn = Str::title(fake()->unique()->words(3, true));

        // Realistic HOOR denim pricing: EGP 450–1,600, stored as piastres.
        $basePrice = Money::fromMajor(fake()->numberBetween(45, 160) * 10);

        return [
            'category_id'  => Category::factory(),
            'name_en'      => $nameEn,
            'name_ar'      => 'منتج '.fake()->unique()->numberBetween(1, 99999),
            'slug'         => Str::slug($nameEn).'-'.fake()->unique()->numberBetween(1, 99999),
            'description_en' => fake()->paragraph(),
            'description_ar' => 'وصف المنتج بالعربية.',
            'base_price'   => $basePrice,
            'sale_price'   => null,
            'status'       => ProductStatus::Published,
            'is_featured'  => false,
            'is_new'       => false,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status'       => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => ProductStatus::Archived]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }

    public function newArrival(): static
    {
        return $this->state(fn (): array => ['is_new' => true]);
    }

    /**
     * Put the product on sale at a given percentage off its base price.
     */
    public function onSale(int $percentage = 20): static
    {
        return $this->state(fn (array $attributes): array => [
            'sale_price' => (int) round($attributes['base_price'] * (100 - $percentage) / 100),
        ]);
    }
}
