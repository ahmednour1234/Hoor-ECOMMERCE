<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GovernorateController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductBulkController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CatalogExportController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\ReturnController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SizeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin dashboard routes
|--------------------------------------------------------------------------
|
| Mounted under /{locale} by routes/web.php and guarded by authentication plus
| the back-office role check.
|
| Models bind by slug (see getRouteKeyName), so admin URLs stay readable.
| Variants and images have no routes of their own: both are edited inline on
| the product form and saved with it in one transaction.
|
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        /*
         * Orders.
         *
         * One listing filtered by status rather than a page per status: the
         * tabs produce the same URLs (?status=shipped) from a single code path.
         * There is no create, edit or destroy — orders are placed at checkout,
         * and the only change staff make is a status transition.
         */
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.status');

        /*
         * Returns and exchanges, filtered by status like the order list.
         *
         * No create: a return is raised by the customer, and the only change
         * staff make is the decision.
         */
        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/{return}', [ReturnController::class, 'show'])->name('returns.show');
        Route::patch('returns/{return}/decide', [ReturnController::class, 'decide'])->name('returns.decide');

        // `show` is omitted throughout: the edit screen is the detail view.
        /*
         * Bulk import.
         *
         * Declared before the resource so "products/import" is not swallowed
         * by the {product} wildcard and read as a slug.
         */
        Route::controller(ProductImportController::class)->prefix('products/import')->name('products.import.')->group(function (): void {
            Route::get('/', 'create')->name('create');
            Route::get('template', 'template')->name('template');
            Route::post('/', 'store')->name('store');
        });

        /*
         * Downloading the catalogue.
         *
         * Declared before both resources for the same reason as the import:
         * "products/export" would otherwise be swallowed by {product} and
         * looked up as a slug.
         */
        Route::get('products/export', [CatalogExportController::class, 'products'])
            ->name('products.export');

        Route::get('categories/export', [CatalogExportController::class, 'categories'])
            ->name('categories.export');

        /*
         * Publishing a batch after an import, which lands products as drafts.
         *
         * POST rather than PATCH deliberately. The form's fields are joined to
         * it by the HTML5 `form` attribute — because wrapping the table would
         * nest each row's delete form inside this one — and a method override
         * depends on a hidden field travelling with them. A plain POST has one
         * less thing to go wrong on the way to the server.
         */
        Route::post('products/bulk', [ProductBulkController::class, 'update'])->name('products.bulk');

        Route::resource('products', ProductController::class)->except('show');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('colors', ColorController::class)->except('show');
        Route::resource('sizes', SizeController::class)->except('show');

        /*
         * Coupons.
         *
         * `show` is kept, unlike elsewhere: the detail screen lists who
         * redeemed the code, which the edit form has no place for.
         */
        Route::resource('coupons', CouponController::class);
        Route::patch('coupons/{coupon}/toggle', [CouponController::class, 'toggle'])
            ->name('coupons.toggle');

        /*
         * Storefront content.
         *
         * Slides and banners are real resources; the settings are one form per
         * group, driven by the registry rather than a route per setting.
         */
        Route::resource('slides', HeroSlideController::class)->except('show');
        Route::resource('banners', BannerController::class)->except('show');
        Route::resource('faqs', FaqController::class)->except('show');

        Route::get('messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::get('messages/{message}', [ContactMessageController::class, 'show'])->name('messages.show');
        Route::patch('messages/{message}', [ContactMessageController::class, 'update'])->name('messages.update');
        Route::patch('messages/{message}/unread', [ContactMessageController::class, 'markUnread'])
            ->name('messages.unread');
        Route::delete('messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');

        Route::get('newsletter', [NewsletterController::class, 'index'])->name('newsletter.index');
        Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('newsletter.export');

        // The group defaults to the first panel, so /admin/settings works.
        Route::get('settings/{group?}', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::patch('settings/{group}', [SettingsController::class, 'update'])->name('settings.update');

        /*
         * Shipping destinations.
         *
         * Areas are nested under their governorate: an area only means
         * anything relative to one, and nesting keeps every action scoped to
         * the right parent.
         */
        Route::resource('governorates', GovernorateController::class)->except('show');
        Route::patch('governorates/{governorate}/toggle', [GovernorateController::class, 'toggle'])
            ->name('governorates.toggle');

        Route::prefix('governorates/{governorate}')->name('governorates.')->group(function (): void {
            Route::resource('areas', AreaController::class)->except('show');
            Route::patch('areas/{area}/toggle', [AreaController::class, 'toggle'])->name('areas.toggle');
        });
    });
