<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\ImageService;
use App\Support\Locale;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Shared instance: the service tracks files written during a request so
        // they can be discarded together if the surrounding work fails.
        $this->app->singleton(ImageService::class);
    }

    public function boot(): void
    {
        $this->registerDefaultLocaleUrlParameter();
        $this->registerGates();
        $this->registerBladeDirectives();
        $this->shareLocaleWithViews();
        $this->shareStoreSettingsWithViews();

        Paginator::useTailwind();
    }

    /**
     * Seed the {locale} URL default before any request is handled.
     *
     * Signed verification and password-reset links are generated from queued
     * jobs and console commands, where SetLocale never runs. Without a default
     * the generator cannot build those locale-prefixed URLs at all.
     */
    private function registerDefaultLocaleUrlParameter(): void
    {
        URL::defaults(['locale' => App::getLocale()]);
    }

    /**
     * Back-office authorization.
     *
     * Every admin screen checks `access-admin` so that a single definition
     * governs both the middleware and any in-view @can checks.
     */
    private function registerGates(): void
    {
        Gate::define('access-admin', static fn (User $user): bool => $user->canAccessAdmin());

        Gate::define('manage-settings', static fn (User $user): bool => $user->is_active && $user->isAdmin());
    }

    /**
     * Direction-aware Blade directives.
     *
     * @rtl / @ltr let components branch on writing direction without repeating
     * the Locale::isRtl() call in markup.
     */
    private function registerBladeDirectives(): void
    {
        Blade::if('rtl', static fn (): bool => Locale::isRtl());
        Blade::if('ltr', static fn (): bool => ! Locale::isRtl());
    }

    private function shareLocaleWithViews(): void
    {
        View::composer('*', static function ($view): void {
            $view->with([
                'currentLocale' => Locale::current(),
                'direction'     => Locale::direction(),
                'isRtl'         => Locale::isRtl(),
            ]);
        });
    }

    /**
     * Make the shop's contact details and settings available to every view.
     *
     * Resolved lazily through the container: the header and footer render on
     * every page and both want a phone number and the social links, and a
     * template that reached for config() directly would keep those values out
     * of the admin's hands.
     */
    private function shareStoreSettingsWithViews(): void
    {
        View::composer('*', static function ($view): void {
            $view->with([
                'contact'  => app(\App\Support\StoreContact::class),
                'settings' => app(\App\Services\SettingsService::class),
            ]);
        });
    }
}
