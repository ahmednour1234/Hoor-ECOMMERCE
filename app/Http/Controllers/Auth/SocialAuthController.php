<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Exceptions\SocialAuthException;
use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Signing in with an outside provider.
 *
 * Two endpoints per provider: one that sends the customer away, one that
 * receives her back. Everything about which account she ends up in is decided
 * by SocialAuthService — this class only moves her between the two.
 */
class SocialAuthController extends Controller
{
    public function __construct(private readonly SocialAuthService $social)
    {
    }

    /**
     * Send the customer to the provider.
     */
    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $this->assertEnabled($provider);

        // Where to return her afterwards. Only a path from this site is kept,
        // so the parameter cannot be used to bounce someone to another domain.
        $request->session()->put('social.intended', $this->safeIntended($request));

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Receive her back and sign her in.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->assertEnabled($provider);

        try {
            $profile = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            // She cancelled, or the provider rejected the exchange. Neither is
            // worth an error page.
            Log::info('Social sign-in did not complete', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => SocialAuthException::failed()->getMessage(),
            ]);
        }

        try {
            $user = $this->social->resolve($provider, $profile);
        } catch (SocialAuthException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => SocialAuthException::inactive()->getMessage(),
            ]);
        }

        Auth::login($user, remember: true);

        // A new session id, so a token captured before sign-in cannot be used
        // after it.
        $request->session()->regenerate();

        $intended = $request->session()->pull('social.intended');

        return redirect()->to($intended ?: route('store.account.index'));
    }

    /**
     * Refuse an unknown or unconfigured provider before Socialite is asked.
     */
    private function assertEnabled(string $provider): void
    {
        if (! $this->social->isEnabled($provider)) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * The page to return to, if it is one of ours.
     *
     * An absolute URL from the query string would be an open redirect: a link
     * that starts on this site and finishes on someone else's, which is exactly
     * the shape a phishing link wants.
     */
    private function safeIntended(Request $request): ?string
    {
        $intended = $request->query('redirect') ?: url()->previous();

        if (! is_string($intended) || $intended === '') {
            return null;
        }

        $host = parse_url($intended, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        // Never bounce her back to a login screen she has just left.
        foreach (['login', 'register', 'forgot-password', 'reset-code', 'reset-password'] as $path) {
            if (str_contains($intended, $path)) {
                return null;
            }
        }

        return $intended;
    }
}
