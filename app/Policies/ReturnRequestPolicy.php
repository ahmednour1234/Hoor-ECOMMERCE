<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;

/**
 * Who may see and act on a return request.
 *
 * Staff run the queue; the customer sees her own and may withdraw one nobody
 * has decided yet.
 */
class ReturnRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, ReturnRequest $request): bool
    {
        return $user->canAccessAdmin() || $request->user_id === $user->id;
    }

    /**
     * Approving, rejecting or completing is staff work.
     */
    public function decide(User $user, ReturnRequest $request): bool
    {
        return $user->canAccessAdmin();
    }

    /**
     * The customer may withdraw her own, until it has been decided.
     */
    public function withdraw(User $user, ReturnRequest $request): bool
    {
        return $request->user_id === $user->id && $request->isCancellable();
    }
}
