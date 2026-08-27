<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Banner>
 */
class BannerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'placement' => 'home_promo',
            'title_en'  => fake()->sentence(3),
            'title_ar'  => 'عرض خاص',
            'position'  => 0,
            'is_active' => true,
        ];
    }
}
