<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CouponRedemption>
 */
class CouponRedemptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'phone'     => '0101'.fake()->numerify('#######'),
            'discount'  => 5000,
        ];
    }
}
