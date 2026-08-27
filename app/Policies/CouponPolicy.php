<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

/**
 * Coupon permissions.
 *
 * Staff run campaigns day to day; deleting is reserved for administrators,
 * because a coupon is the record of a discount the business promised.
 */
class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $user->canAccessAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $user->canAccessAdmin();
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
