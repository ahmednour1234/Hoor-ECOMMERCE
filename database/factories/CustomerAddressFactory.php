<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Area;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'   => User::factory(),
            'label'     => fake()->randomElement(['Home', 'Work', null]),
            'full_name' => fake()->name(),

            // Real Egyptian mobile shape, so validation in tests is exercised
            // rather than bypassed.
            'phone'     => '01'.fake()->randomElement(['0', '1', '2', '5']).fake()->numerify('########'),
            'phone_alt' => null,

            'governorate_id' => Governorate::factory(),
            'area_id'        => null,

            'address'    => fake()->streetAddress(),
            'landmark'   => null,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    /**
     * Attach an area that genuinely belongs to the address's governorate.
     */
    public function withArea(): static
    {
        return $this->afterCreating(function ($address): void {
            $area = Area::factory()->create(['governorate_id' => $address->governorate_id]);

            $address->update(['area_id' => $area->id]);
        });
    }
}
