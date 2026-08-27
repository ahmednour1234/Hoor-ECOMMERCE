<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Catalog permissions.
 *
 * Staff maintain the catalog day to day; permanent deletion is reserved for
 * administrators because it destroys files and cannot be undone.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->canAccessAdmin();
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->canAccessAdmin();
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
