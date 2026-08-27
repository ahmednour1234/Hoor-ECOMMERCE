<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Size;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Size>
 */
class SizeFactory extends Factory
{
    protected $model = Size::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('SZ##'));

        return [
            'name_en'    => $code,
            'name_ar'    => $code,
            'code'       => $code,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active'  => true,
        ];
    }
}
