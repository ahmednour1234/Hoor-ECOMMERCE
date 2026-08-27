<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetLocale;
use App\Support\Locale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'locale' => SetLocale::class,
            'admin'  => EnsureUserIsAdmin::class,
        ]);

        // Runs after the session is started, so the visitor's remembered
        // language is available as a fallback when a URL carries no prefix.
        $middleware->web(append: [SetLocale::class]);

        // The `auth` middleware may redirect a guest before SetLocale has run,
        // so the locale is read straight from the request path here rather than
        // relying on a registered URL default.
        $middleware->redirectGuestsTo(
            static fn (Request $request): string => route('login', [
                'locale' => Locale::fromRequest($request),
            ]),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
