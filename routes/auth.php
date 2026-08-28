<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetCodeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    /*
     * Password reset by emailed code, in three steps.
     *
     * A code rather than a signed link: links are rewritten by some email
     * clients and cannot be followed at all by someone reading her mail on a
     * phone while browsing on a laptop.
     *
     * The email under reset lives in the session, not the URL — in a query
     * string it would sit in history and logs, and could be swapped for
     * someone else's between steps. That is why the later steps take no
     * parameters.
     */
    /*
     * Signing in with an outside provider.
     *
     * Outside the {locale} group would be simpler, but the callback has to
     * return the customer to a locale-prefixed page, and Socialite needs the
     * redirect URI to match exactly — so both live here and the locale rides
     * along.
     */
    Route::controller(SocialAuthController::class)->group(function (): void {
        Route::get('auth/{provider}', 'redirect')
            ->whereIn('provider', \App\Services\SocialAuthService::PROVIDERS)
            ->name('social.redirect');

        Route::get('auth/{provider}/callback', 'callback')
            ->whereIn('provider', \App\Services\SocialAuthService::PROVIDERS)
            ->name('social.callback');
    });

    Route::controller(PasswordResetCodeController::class)->group(function (): void {
        // 1. Ask for a code.
        Route::get('forgot-password', 'create')->name('password.request');
        Route::post('forgot-password', 'store')->name('password.email');

        // 2. Enter it.
        Route::get('reset-code', 'showCodeForm')->name('password.code');
        Route::post('reset-code', 'verifyCode')->name('password.code.verify');
        Route::post('reset-code/resend', 'resend')->name('password.code.resend');

        // 3. Choose a new password. Named password.reset because Laravel's own
        // notification and redirects reference that name.
        Route::get('reset-password', 'showPasswordForm')->name('password.reset');
        Route::post('reset-password', 'updatePassword')->name('password.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
