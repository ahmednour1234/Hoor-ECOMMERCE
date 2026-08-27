<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ReturnRequest>
 */
class ReturnRequestFactory extends Factory
{
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'number'   => sprintf('RET-%d-%06d', now()->year, $sequence),
            'order_id' => Order::factory(),
            'user_id'  => null,
            'type'     => ReturnType::Return_,
            'status'   => ReturnStatus::Requested,
            'reason'   => fake()->randomElement(ReturnReason::cases()),
            'customer_note' => null,
        ];
    }

    public function status(ReturnStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function exchange(): static
    {
        return $this->state(['type' => ReturnType::Exchange]);
    }
}
