<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Color>
 */
class ColorFactory extends Factory
{
    protected $model = Color::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nameEn = Str::title(fake()->unique()->word());

        return [
            'name_en'    => $nameEn,
            'name_ar'    => 'لون '.fake()->unique()->numberBetween(1, 9999),
            'slug'       => Str::slug($nameEn).'-'.fake()->unique()->numberBetween(1, 99999),
            'hex'        => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 30),
            'is_active'  => true,
        ];
    }
}
