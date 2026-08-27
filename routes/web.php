<?php

declare(strict_types=1);

use App\Http\Controllers\LocaleController;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Locale-aware route architecture
|--------------------------------------------------------------------------
|
| Every user-facing route lives beneath a /{locale} prefix (/en/... or /ar/...)
| so that each page has one canonical, shareable, indexable URL per language.
|
| Unprefixed URLs are redirected to the visitor's best-matching locale rather
| than being served directly, which avoids duplicate content across two URLs.
|
*/

$localePattern = implode('|', Locale::codes());

// Bare "/" and any legacy unprefixed path resolve to a locale-prefixed URL.
Route::get('/', [LocaleController::class, 'root'])->name('root');

// Explicit language switch: preserves the current page across locales.
Route::get('/language/{locale}', [LocaleController::class, 'switch'])
    ->whereIn('locale', Locale::codes())
    ->name('locale.switch');

Route::prefix('{locale}')
    ->where(['locale' => $localePattern])
    ->middleware('locale')
    ->group(function (): void {
        require __DIR__.'/store.php';
        require __DIR__.'/admin.php';
        require __DIR__.'/auth.php';
    });
