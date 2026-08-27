<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;

/**
 * Central authority for locale resolution and presentation.
 *
 * Locales are always expressed in the URL as the first path segment
 * (/en/... or /ar/...), which keeps every page independently indexable
 * and shareable without relying on session state.
 */
final class Locale
{
    public const FALLBACK = 'en';

    /**
     * All configured locales keyed by their code.
     *
     * @return array<string, array{name: string, native: string, direction: string, html_lang: string, flag: string}>
     */
    public static function all(): array
    {
        return Config::get('hoor.locales', []);
    }

    /**
     * Supported locale codes, in switcher display order.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::all());
    }

    public static function current(): string
    {
        $locale = App::getLocale();

        return self::isSupported($locale) ? $locale : self::FALLBACK;
    }

    /**
     * Metadata for the given locale, falling back to the default locale.
     *
     * @return array{name: string, native: string, direction: string, html_lang: string, flag: string}
     */
    public static function meta(?string $locale = null): array
    {
        $locale = $locale ?? self::current();

        return self::all()[$locale] ?? self::all()[self::FALLBACK];
    }

    public static function direction(?string $locale = null): string
    {
        return self::meta($locale)['direction'];
    }

    public static function isRtl(?string $locale = null): bool
    {
        return self::direction($locale) === 'rtl';
    }

    public static function htmlLang(?string $locale = null): string
    {
        return self::meta($locale)['html_lang'];
    }

    public static function native(?string $locale = null): string
    {
        return self::meta($locale)['native'];
    }

    /**
     * The locales the user can switch to from the current one.
     *
     * @return array<string, array{name: string, native: string, direction: string, html_lang: string, flag: string}>
     */
    public static function alternates(): array
    {
        return array_diff_key(self::all(), [self::current() => true]);
    }

    /**
     * Rewrite the current URL so it points at the same page in another locale.
     *
     * The locale prefix is swapped in place, so query strings and deep paths
     * survive the switch. Unknown paths degrade gracefully to the locale root.
     */
    public static function urlFor(string $locale, ?string $url = null): string
    {
        if (! self::isSupported($locale)) {
            $locale = self::FALLBACK;
        }

        $url = $url ?? Request::fullUrl();
        $parts = parse_url($url);
        $path = trim($parts['path'] ?? '/', '/');

        $segments = $path === '' ? [] : explode('/', $path);

        if ($segments !== [] && self::isSupported($segments[0])) {
            $segments[0] = $locale;
        } else {
            array_unshift($segments, $locale);
        }

        $target = URL::to(implode('/', $segments));

        return isset($parts['query']) ? $target.'?'.$parts['query'] : $target;
    }

    /**
     * Resolve the locale straight from a request's path.
     *
     * Used where the URL default has not been registered yet — most notably
     * the guest redirect, which the `auth` middleware may trigger before
     * SetLocale has run.
     */
    public static function fromRequest(HttpRequest $request): string
    {
        $first = $request->segment(1);

        if (self::isSupported($first)) {
            return $first;
        }

        $remembered = $request->hasSession() ? $request->session()->get('locale') : null;

        if (self::isSupported($remembered)) {
            return $remembered;
        }

        return config('app.locale', self::FALLBACK);
    }

    /**
     * Pick the best supported locale advertised by the browser.
     */
    public static function fromBrowser(): ?string
    {
        $preferred = Request::getPreferredLanguage(self::codes());

        return self::isSupported($preferred) ? $preferred : null;
    }

    /**
     * Choose a localized value from a model or array using the current locale,
     * falling back to the default locale when the translation is empty.
     */
    public static function pick(object|array $source, string $attribute, ?string $locale = null): ?string
    {
        $locale = $locale ?? self::current();

        $read = static function (string $key) use ($source): ?string {
            $value = is_array($source) ? ($source[$key] ?? null) : ($source->{$key} ?? null);

            return is_string($value) && $value !== '' ? $value : null;
        };

        return $read("{$attribute}_{$locale}") ?? $read($attribute.'_'.self::FALLBACK);
    }
}
