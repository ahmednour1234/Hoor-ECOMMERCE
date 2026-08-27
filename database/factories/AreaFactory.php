<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Casts\Money;
use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name_en'        => fake()->unique()->streetName(),
            'name_ar'        => 'منطقة '.fake()->unique()->numberBetween(1, 9999),
            // Null by default: areas inherit unless deliberately overridden.
            'shipping_fee'   => null,
            'is_active'      => true,
            'sort_order'     => fake()->numberBetween(0, 30),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function fee(int $egp): static
    {
        return $this->state(fn (): array => ['shipping_fee' => Money::fromMajor($egp)]);
    }
}
