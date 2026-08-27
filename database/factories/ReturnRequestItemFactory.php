<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\ReturnRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ReturnRequestItem>
 */
class ReturnRequestItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'return_request_id' => ReturnRequest::factory(),
            'order_item_id'     => OrderItem::factory(),
            'quantity'          => 1,
        ];
    }
}
