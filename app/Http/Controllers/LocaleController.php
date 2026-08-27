<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Entry points that resolve a visitor to a locale-prefixed URL.
 */
class LocaleController extends Controller
{
    /**
     * Redirect the unprefixed root to the visitor's best-matching locale.
     */
    public function root(Request $request): RedirectResponse
    {
        $locale = $request->session()->get('locale')
            ?? Locale::fromBrowser()
            ?? config('app.locale', Locale::FALLBACK);

        if (! Locale::isSupported($locale)) {
            $locale = Locale::FALLBACK;
        }

        return redirect()->route('store.home', ['locale' => $locale]);
    }

    /**
     * Switch language while staying on the same page.
     *
     * The page to return to is taken from the referer so the visitor is not
     * bounced to the home page mid-journey; it falls back to the storefront
     * home when no trustworthy referer is present.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locale::isSupported($locale), 404);

        $request->session()->put('locale', $locale);

        $referer = $request->headers->get('referer');

        $target = $this->isInternal($referer)
            ? Locale::urlFor($locale, $referer)
            : route('store.home', ['locale' => $locale]);

        return redirect()->to($target);
    }

    /**
     * Guard against open redirects by only trusting same-host referers.
     */
    private function isInternal(?string $url): bool
    {
        if ($url === null || $url === '') {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host === null || $host === request()->getHost();
    }
}
