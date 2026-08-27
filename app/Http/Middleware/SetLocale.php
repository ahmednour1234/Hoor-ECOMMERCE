<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale from the route prefix and boots the application
 * around it.
 *
 * Resolution order:
 *   1. The {locale} route parameter (authoritative — the URL always wins).
 *   2. The locale remembered in the session from a previous visit.
 *   3. The browser's Accept-Language header.
 *   4. The application fallback locale.
 *
 * The resolved locale is registered as a default URL parameter so that
 * route('store.home') and friends never need the locale passed explicitly.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        // Every localized route carries a {locale} segment; binding it here
        // keeps route() calls throughout the app free of the parameter.
        URL::defaults(['locale' => $locale]);

        // Strip the parameter only where it is a pure URL prefix, so that
        // controllers which genuinely accept a locale argument (the language
        // switcher) still receive it.
        $route = $request->route();

        if ($route !== null && str_starts_with((string) $route->uri(), '{locale}')) {
            $route->forgetParameter('locale');
        }

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $candidates = [
            $request->route('locale'),
            $request->session()->get('locale'),
            Locale::fromBrowser(),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && Locale::isSupported($candidate)) {
                return $candidate;
            }
        }

        return config('app.locale', Locale::FALLBACK);
    }
}
