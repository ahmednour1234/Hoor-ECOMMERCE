<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CustomerAddress;
use App\Models\User;

/**
 * An address book belongs to exactly one customer.
 *
 * Staff are not granted access: there is no back-office screen for editing a
 * customer's saved addresses, and an unused permission is one nobody audits.
 */
class CustomerAddressPolicy
{
    public function view(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function update(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id;
    }

    public function delete(User $user, CustomerAddress $address): bool
    {
        return $address->user_id === $user->id;
    }
}
