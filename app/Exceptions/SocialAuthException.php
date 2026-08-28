<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A social sign-in that cannot proceed, with a reason the customer can act on.
 *
 * Private constructor and named factories, so every refusal states which rule
 * it broke rather than passing an ad-hoc string.
 */
class SocialAuthException extends RuntimeException
{
    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    /**
     * The provider gave us no email, so there is nothing to identify the
     * customer by.
     */
    public static function noEmail(): self
    {
        return new self('no_email', __('auth.social.errors.no_email'));
    }

    /**
     * The email belongs to an existing account, but the provider has not
     * verified that this person owns it — so linking would be a takeover.
     */
    public static function emailNotVerified(string $email): self
    {
        return new self('email_not_verified', __('auth.social.errors.email_not_verified', [
            'email' => $email,
        ]));
    }

    /**
     * The provider is unknown or has no credentials configured.
     */
    public static function unsupported(): self
    {
        return new self('unsupported', __('auth.social.errors.unsupported'));
    }

    /**
     * The customer cancelled, or the provider returned an error.
     */
    public static function failed(): self
    {
        return new self('failed', __('auth.social.errors.failed'));
    }

    /**
     * The account exists but has been deactivated.
     */
    public static function inactive(): self
    {
        return new self('inactive', __('auth.social.errors.inactive'));
    }
}
