<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the initial back-office account.
 *
 * Credentials come from the environment so that production never seeds a
 * publicly known password. The seeder is idempotent — re-running it updates the
 * role and activity flag without touching an already-rotated password.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('HOOR_ADMIN_EMAIL', 'admin@hoor.eg');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name'              => env('HOOR_ADMIN_NAME', 'HOOR Admin'),
                'password'          => Hash::make(env('HOOR_ADMIN_PASSWORD', 'password')),
                'role'              => UserRole::Admin,
                'is_active'         => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
