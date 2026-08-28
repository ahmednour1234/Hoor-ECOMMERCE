<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Password reset by a code sent to the customer's email.
 *
 * A six-digit code rather than a signed link: links break when an email client
 * rewrites them, and a customer reading her mail on a phone but browsing on a
 * laptop cannot follow one at all. A code she can type works from anywhere.
 *
 * The code is stored **hashed**, exactly as a password is. A reset code is a
 * temporary credential — anyone holding the plaintext can take the account —
 * so a leaked database backup must not hand over live codes.
 *
 * Three defences, because six digits is only a million combinations:
 *
 *   - the code expires (config: auth.passwords.users.expire, in minutes);
 *   - each code counts its own failed attempts and is destroyed once they run
 *     out, so rotating IP addresses does not buy more guesses;
 *   - requesting a new code replaces the old one, so only ever one is live.
 */
class PasswordResetCodeService
{
    /**
     * Six digits: short enough to type from a phone screen, long enough that
     * the attempt limit does the rest of the work.
     */
    private const LENGTH = 6;

    /**
     * Wrong guesses before the code is thrown away.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Issue a code for an email, replacing any code already outstanding.
     *
     * Returns the plaintext, which exists only long enough to be mailed. It is
     * never stored and never logged.
     */
    public function issue(string $email): string
    {
        $code = $this->generate();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $this->normalise($email)],
            [
                'token'      => Hash::make($this->normaliseCode($code)),
                'attempts'   => 0,
                'created_at' => now(),
            ],
        );

        return $code;
    }

    /**
     * Whether this code is the live one for this email.
     *
     * A failed check costs the code one of its attempts, and spends the last
     * one by deleting it outright — the customer must then request a new code,
     * which is a small inconvenience for her and the end of the road for a
     * script.
     */
    public function verify(string $email, string $code): bool
    {
        $email = $this->normalise($email);
        $record = $this->recordFor($email);

        if ($record === null) {
            return false;
        }

        if ($this->hasExpired($record)) {
            $this->forget($email);

            return false;
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $this->forget($email);

            return false;
        }

        if (! Hash::check($this->normaliseCode($code), $record->token)) {
            $this->recordFailure($email, $record->attempts + 1);

            return false;
        }

        return true;
    }

    /**
     * Verify and immediately spend the code.
     *
     * Used at the moment the password actually changes, so a code cannot be
     * replayed to change it a second time.
     */
    public function consume(string $email, string $code): bool
    {
        if (! $this->verify($email, $code)) {
            return false;
        }

        $this->forget($email);

        return true;
    }

    /**
     * Whether a code exists for this email, live or not.
     *
     * Used to decide whether to show the "enter your code" step at all.
     */
    public function hasPending(string $email): bool
    {
        $record = $this->recordFor($this->normalise($email));

        return $record !== null && ! $this->hasExpired($record);
    }

    public function forget(string $email): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $this->normalise($email))
            ->delete();
    }

    /**
     * How long a code stays valid, in minutes.
     *
     * Read from the same config Laravel's own broker uses, so the two cannot
     * disagree about it.
     */
    public function lifetimeMinutes(): int
    {
        return (int) config('auth.passwords.users.expire', 60);
    }

    /**
     * How long before another code may be requested, in seconds.
     */
    public function throttleSeconds(): int
    {
        return (int) config('auth.passwords.users.throttle', 60);
    }

    /**
     * Whether a fresh code was requested too recently.
     *
     * Without this, the endpoint is a way to send someone unlimited email.
     */
    public function recentlyIssued(string $email): bool
    {
        $record = $this->recordFor($this->normalise($email));

        if ($record === null) {
            return false;
        }

        return Carbon::parse($record->created_at)
            ->addSeconds($this->throttleSeconds())
            ->isFuture();
    }

    /**
     * Find the account a code belongs to.
     *
     * Deliberately not part of verify(): the reset screens must behave
     * identically whether or not an address is registered, so that the form
     * cannot be used to discover which emails have accounts.
     */
    public function userFor(string $email): ?User
    {
        return User::query()->where('email', $this->normalise($email))->first();
    }

    // ----------------------------------------------------------------- Internals

    private function generate(): string
    {
        // random_int is cryptographically secure; rand() and mt_rand() are not,
        // and this is a credential.
        return str_pad((string) random_int(0, 999999), self::LENGTH, '0', STR_PAD_LEFT);
    }

    private function recordFor(string $email): ?object
    {
        return DB::table('password_reset_tokens')->where('email', $email)->first();
    }

    private function hasExpired(object $record): bool
    {
        return Carbon::parse($record->created_at)
            ->addMinutes($this->lifetimeMinutes())
            ->isPast();
    }

    private function recordFailure(string $email, int $attempts): void
    {
        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->forget($email);

            return;
        }

        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->update(['attempts' => $attempts]);
    }

    private function normalise(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Arabic-Indic digits are common on Egyptian keyboards, and a customer
     * reading ٠١٢ from her screen may well type it back.
     */
    private function normaliseCode(string $code): string
    {
        return preg_replace('/\D/', '', \App\Support\EgyptianPhone::toLatinDigits(trim($code))) ?? '';
    }
}
