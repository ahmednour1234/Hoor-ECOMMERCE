<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Managing site content and settings.
 *
 * One gate for the whole content module — slides, banners, the inbox, the
 * newsletter and the settings themselves. Splitting it per model would give
 * five permissions that are always granted together, which is five chances for
 * them to drift apart.
 */
class SettingPolicy
{
    public function manage(User $user): bool
    {
        return $user->canAccessAdmin();
    }
}
