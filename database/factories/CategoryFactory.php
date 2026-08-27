<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nameEn = fake()->unique()->words(2, true);

        return [
            'parent_id'  => null,
            'name_en'    => Str::title($nameEn),
            'name_ar'    => 'قسم '.fake()->unique()->numberBetween(1, 9999),
            'slug'       => Str::slug($nameEn).'-'.fake()->unique()->numberBetween(1, 99999),
            'is_active'  => true,
            'sort_order' => fake()->numberBetween(0, 50),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (): array => ['parent_id' => $parent->id]);
    }
}
