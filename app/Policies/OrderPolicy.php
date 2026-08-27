<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Order permissions.
 *
 * Staff run the back office day to day, so they may read orders and move them
 * through their statuses. Orders are never deleted: an order is a financial
 * record, and cancelling it is what "removing" it means.
 */
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->canAccessAdmin() || $order->user_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
