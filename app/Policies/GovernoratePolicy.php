<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Governorate;
use App\Models\User;

/**
 * Shipping configuration permissions.
 *
 * Staff maintain destinations and fees day to day; deletion is reserved for
 * administrators because a destination may be referenced by past orders.
 */
class GovernoratePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Governorate $model): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Governorate $model): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, Governorate $model): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
