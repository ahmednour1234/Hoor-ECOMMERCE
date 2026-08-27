<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Casts\Money;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Governorate>
 */
class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name_en'           => $name,
            'name_ar'           => 'محافظة '.fake()->unique()->numberBetween(1, 9999),
            'code'              => strtoupper(fake()->unique()->bothify('G##')),
            'shipping_fee'      => Money::fromMajor(fake()->numberBetween(40, 95)),
            'delivery_days_min' => 2,
            'delivery_days_max' => 5,
            'is_active'         => true,
            'sort_order'        => fake()->numberBetween(0, 30),
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
