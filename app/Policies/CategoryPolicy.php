<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->canAccessAdmin();
    }

    /**
     * Structural changes to the catalog tree are an administrator decision.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
