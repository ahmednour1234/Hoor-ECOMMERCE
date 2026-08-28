<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Signing in through an outside provider.
 *
 * The rule that matters most is what happens when the provider's email already
 * belongs to a HOOR account. Linking on a matching email alone is an account
 * takeover: anyone can create a Google account claiming an address they do not
 * own, and would then inherit the HOOR account behind it.
 *
 * So the two are linked only when the provider states the email is **verified**
 * at their end. Google does say this. An unverified email is refused with an
 * explanation rather than silently creating a second account, which would leave
 * the customer with two histories and neither of them complete.
 */
class SocialAuthService
{
    /**
     * Providers the application accepts.
     *
     * Checked before the name reaches Socialite, so a crafted URL cannot make
     * the application attempt a driver that was never configured.
     *
     * @var list<string>
     */
    public const PROVIDERS = ['google'];

    /**
     * Whether a provider is both known and configured.
     *
     * The sign-in button is hidden when this is false, so a shop that has not
     * set its credentials shows no button rather than one that leads to an
     * error page.
     */
    public function isEnabled(string $provider): bool
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            return false;
        }

        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    /**
     * Find or create the HOOR account behind a provider profile.
     *
     * @throws SocialAuthException
     */
    public function resolve(string $provider, SocialiteUser $profile): User
    {
        $providerId = (string) $profile->getId();
        $email = $this->normaliseEmail($profile->getEmail());

        // 1. Already linked. The provider id is the key, not the email: people
        //    change their email address, and the id is what identifies the
        //    account at the far end.
        $existing = SocialAccount::query()
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($existing !== null) {
            $this->refresh($existing, $profile, $email);

            return $existing->user;
        }

        if ($email === null) {
            throw SocialAuthException::noEmail();
        }

        // 2. An account with this email already exists.
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            if (! $this->providerVerifiedTheEmail($profile)) {
                // Refusing is the whole point: without this, anyone who can
                // create a provider account naming her address takes hers.
                throw SocialAuthException::emailNotVerified($email);
            }

            $this->link($user, $provider, $providerId, $profile, $email);

            return $user;
        }

        // 3. Nobody here yet.
        return $this->register($provider, $providerId, $profile, $email);
    }

    /**
     * Create a HOOR account from a provider profile.
     */
    private function register(string $provider, string $providerId, SocialiteUser $profile, string $email): User
    {
        return DB::transaction(function () use ($provider, $providerId, $profile, $email): User {
            $user = User::create([
                'name'  => $this->nameFrom($profile, $email),
                'email' => $email,
                'role'  => UserRole::Customer,

                /*
                 * No password at all, rather than a random one.
                 *
                 * A random password looks like a credential she could reset
                 * into, and every "does this account have a password" check
                 * would be wrong about her. Null says plainly that she signs
                 * in with a provider.
                 */
                'password' => null,

                'is_active' => true,
            ]);

            /*
             * Set outside create(), because email_verified_at is deliberately
             * not mass-assignable — it is the flag that decides whether an
             * address has been proved, so it must never be settable from a
             * request payload.
             *
             * The provider verified the address, which is the same assurance
             * our own verification email buys, so asking her to prove it again
             * would be asking her to prove something already proven.
             */
            if ($this->providerVerifiedTheEmail($profile)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $this->link($user, $provider, $providerId, $profile, $email);

            return $user;
        });
    }

    private function link(User $user, string $provider, string $providerId, SocialiteUser $profile, ?string $email): void
    {
        SocialAccount::updateOrCreate(
            ['provider' => $provider, 'provider_id' => $providerId],
            [
                'user_id' => $user->id,
                'email'   => $email,
                'name'    => $profile->getName(),
                'avatar'  => $profile->getAvatar(),
            ],
        );
    }

    /**
     * Keep the stored profile current, for support enquiries.
     */
    private function refresh(SocialAccount $account, SocialiteUser $profile, ?string $email): void
    {
        $account->update([
            'email'  => $email ?? $account->email,
            'name'   => $profile->getName() ?? $account->name,
            'avatar' => $profile->getAvatar() ?? $account->avatar,
        ]);
    }

    /**
     * Whether the provider says it has verified this email itself.
     *
     * Socialite exposes the raw payload; Google sets `email_verified`. A
     * provider that does not say is treated as not verified, because the
     * consequence of guessing wrong is somebody else's account.
     */
    private function providerVerifiedTheEmail(SocialiteUser $profile): bool
    {
        $raw = method_exists($profile, 'getRaw') ? $profile->getRaw() : [];

        return (bool) ($raw['email_verified'] ?? false);
    }

    private function nameFrom(SocialiteUser $profile, string $email): string
    {
        $name = trim((string) $profile->getName());

        // Falling back to the local part of the address is better than an
        // empty name on every order and greeting.
        return $name !== '' ? $name : \Illuminate\Support\Str::before($email, '@');
    }

    private function normaliseEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }
}
