<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

/**
 * Shipping configuration permissions.
 *
 * Staff maintain destinations and fees day to day; deletion is reserved for
 * administrators because a destination may be referenced by past orders.
 */
class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Area $model): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Area $model): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, Area $model): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
