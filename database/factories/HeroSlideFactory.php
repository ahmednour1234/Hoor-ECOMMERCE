<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'image_path' => 'hero/hero-1.jpg',
            'backdrop'   => '#CAB296',
            'position'   => 0,
            'is_active'  => true,
        ];
    }
}
