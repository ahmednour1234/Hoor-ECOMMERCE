<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Services\PasswordResetCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Resetting a password with a code sent by email.
 *
 * Three steps: ask for the code, enter it, choose a new password.
 *
 * The governing rule throughout is that **none of these screens may reveal
 * whether an email is registered**. A form that says "no account with that
 * address" is a way to test whether someone shops here, so every path answers
 * the same way whether or not the account exists — the mail is simply not sent.
 *
 * The email being reset is kept in the session rather than the URL. In a query
 * string it would sit in browser history and server logs, and could be swapped
 * for someone else's between steps.
 */
class PasswordResetCodeController extends Controller
{
    /**
     * Where the email under reset is remembered between steps.
     */
    private const SESSION_EMAIL = 'password_reset.email';

    /**
     * Set once the code has been checked, so the final step cannot be reached
     * by typing its URL.
     */
    private const SESSION_VERIFIED = 'password_reset.verified';

    public function __construct(private readonly PasswordResetCodeService $codes)
    {
    }

    // --------------------------------------------------------------- Step one

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a code, if the address belongs to an account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:190']]);

        $email = strtolower(trim($validated['email']));

        // Two limits: this one stops one address being mailed repeatedly, and
        // the IP limit below stops one client walking a list of addresses.
        if ($this->tooManyRequests($request, $email)) {
            return back()
                ->withInput()
                ->withErrors(['email' => __('auth.reset.throttled')]);
        }

        $user = $this->codes->userFor($email);

        if ($user !== null) {
            $code = $this->codes->issue($email);

            $this->deliver($email, $code);
        }

        $request->session()->put(self::SESSION_EMAIL, $email);
        $request->session()->forget(self::SESSION_VERIFIED);

        // The same answer either way: the customer is told a code was sent if
        // the address is registered, which is true, and reveals nothing if it
        // is not.
        return redirect()
            ->route('password.code')
            ->with('status', __('auth.reset.sent'));
    }

    // --------------------------------------------------------------- Step two

    public function showCodeForm(Request $request): RedirectResponse|View
    {
        if (! $request->session()->has(self::SESSION_EMAIL)) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-code', [
            'email'   => $request->session()->get(self::SESSION_EMAIL),
            'minutes' => $this->codes->lifetimeMinutes(),
        ]);
    }

    /**
     * Check the code the customer typed.
     */
    public function verifyCode(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('password.request');
        }

        $request->validate(['code' => ['required', 'string', 'max:12']]);

        if (! $this->codes->verify($email, (string) $request->input('code'))) {
            return back()->withErrors(['code' => __('auth.reset.invalid_code')]);
        }

        // Marks the final step reachable. The code itself is spent only when
        // the password actually changes, so a customer who gets this far but
        // abandons the form can come back to it.
        $request->session()->put(self::SESSION_VERIFIED, true);

        return redirect()->route('password.reset');
    }

    /**
     * Send another code, for a customer whose first one never arrived.
     */
    public function resend(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null) {
            return redirect()->route('password.request');
        }

        if ($this->codes->recentlyIssued($email)) {
            return back()->withErrors(['code' => __('auth.reset.throttled')]);
        }

        if ($this->codes->userFor($email) !== null) {
            $this->deliver($email, $this->codes->issue($email));
        }

        return back()->with('status', __('auth.reset.sent'));
    }

    // ------------------------------------------------------------- Step three

    public function showPasswordForm(Request $request): RedirectResponse|View
    {
        if (! $request->session()->get(self::SESSION_VERIFIED)) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password-code');
    }

    /**
     * Set the new password.
     *
     * The code is re-checked here, not merely trusted from the session: the
     * session flag says the customer proved the code once, but it may have
     * expired since, and it must be spent exactly once.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::SESSION_EMAIL);

        if ($email === null || ! $request->session()->get(self::SESSION_VERIFIED)) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'code'     => ['required', 'string', 'max:12'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! $this->codes->consume($email, (string) $request->input('code'))) {
            return back()->withErrors(['code' => __('auth.reset.invalid_code')]);
        }

        $user = $this->codes->userFor($email);

        if ($user === null) {
            return redirect()->route('password.request')->withErrors(['email' => __('auth.reset.failed')]);
        }

        $user->forceFill([
            'password'       => Hash::make((string) $request->input('password')),
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        // Everything about the reset is finished with.
        $request->session()->forget([self::SESSION_EMAIL, self::SESSION_VERIFIED]);

        return redirect()->route('login')->with('status', __('auth.reset.done'));
    }

    // ------------------------------------------------------------- Internals

    /**
     * Queue the code, and never let a mail failure surface as a reset failure.
     */
    private function deliver(string $email, string $code): void
    {
        try {
            Mail::to($email)
                ->locale(app()->getLocale())
                ->send(new PasswordResetCode($code, $this->codes->lifetimeMinutes()));
        } catch (\Throwable $e) {
            // The code itself is never logged: it is a live credential.
            Log::warning('Password reset code could not be sent', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Per-address and per-IP limits together.
     *
     * The address limit stops one inbox being flooded; the IP limit stops one
     * client walking a list of addresses to see which are registered.
     */
    private function tooManyRequests(Request $request, string $email): bool
    {
        if ($this->codes->recentlyIssued($email)) {
            return true;
        }

        $key = 'reset-code:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 10)) {
            return true;
        }

        RateLimiter::hit($key, decaySeconds: 600);

        return false;
    }
}
