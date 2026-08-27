<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'code'               => 'SAVE'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'name_en'            => 'Test campaign',
            'type'               => CouponType::Fixed,
            // 50 EGP, in piastres.
            'value'              => 5000,
            'max_discount'       => null,
            'min_order'          => null,
            'usage_limit'        => null,
            'per_customer_limit' => null,
            'is_active'          => true,
        ];
    }

    public function fixed(int $piastres): static
    {
        return $this->state(['type' => CouponType::Fixed, 'value' => $piastres]);
    }

    public function percentage(int $percent, ?int $cap = null): static
    {
        return $this->state([
            'type'         => CouponType::Percentage,
            'value'        => $percent,
            'max_discount' => $cap,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'starts_at'  => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(['starts_at' => now()->addWeek()]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
