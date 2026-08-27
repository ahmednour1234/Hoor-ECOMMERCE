<?php

declare(strict_types=1);

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\DashboardController as AccountController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\ReturnController as AccountReturnController;
use App\Http\Controllers\Account\WishlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\OrderTrackingController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront routes
|--------------------------------------------------------------------------
|
| Mounted under /{locale} by routes/web.php. Catalog, cart, checkout and
| account routes are added in later phases.
|
*/

Route::name('store.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');

    // Filter state lives in the query string, so a shop URL is shareable:
    // /en/shop?category=jeans&size=m&color=indigo&sort=price_asc
    Route::get('/shop', ShopController::class)->name('shop');

    // Products bind by slug (see Product::getRouteKeyName).
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // Adding to the cart is scoped to the product, so the server can confirm a
    // variant genuinely belongs to it rather than trusting the submitted id.
    Route::post('/products/{product}/cart', [CartController::class, 'store'])->name('cart.store');

    // The cart itself lives in the session, so guests need no account.
    Route::prefix('cart')->name('cart.')->controller(CartController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::patch('/', 'update')->name('update');
        Route::delete('/clear', 'clear')->name('clear');

        // A code is applied to the basket so the discount is visible before
        // checkout; it carries through when she continues.
        Route::post('/coupon', 'applyCoupon')->name('coupon.apply');
        Route::delete('/coupon', 'removeCoupon')->name('coupon.remove');
        Route::delete('/{variant}', 'destroy')->whereNumber('variant')->name('destroy');
    });

    // Checkout is cash on delivery only and open to guests.
    Route::prefix('checkout')->name('checkout.')->controller(CheckoutController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/quote', 'quote')->name('quote');
        Route::post('/', 'store')->name('store');
        Route::get('/{order}/confirmed', 'success')->name('success');
    });

    /*
     * The pages the admin writes.
     *
     * Content comes from settings, so rewording the shop's own story does not
     * need a deployment.
     */
    Route::get('/about', [PageController::class, 'about'])->name('pages.about');
    Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
    Route::post('/contact', [PageController::class, 'storeMessage'])->name('pages.contact.send');

    // Open to guests: asking for an account to join a mailing list loses the
    // subscriber.
    Route::post('/newsletter', [PageController::class, 'subscribe'])->name('newsletter.subscribe');

    /*
     * Public order tracking.
     *
     * No account required: the order number and the phone it was placed with
     * are the credential. Orders bind by number, never by id, so the detail
     * URL cannot be walked from one customer's order to the next.
     */
    Route::prefix('track')->name('tracking.')->controller(OrderTrackingController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'lookup')->name('lookup');
        Route::get('/{order}', 'show')->name('show');
    });

    /*
     * The customer account.
     *
     * Registration stays optional throughout: everything here has a guest
     * equivalent (checkout, tracking), and nothing in the buying journey
     * redirects to a login form.
     */
    Route::middleware('auth')->prefix('account')->name('account.')->group(function (): void {
        Route::get('/', AccountController::class)->name('index');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // Orders bind by number (see Order::getRouteKeyName) and ownership is
        // checked in the controller, since binding alone would resolve anyone's.
        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');

        Route::resource('addresses', AddressController::class)->except('show');
        Route::patch('addresses/{address}/default', [AddressController::class, 'makeDefault'])
            ->name('addresses.default');

        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
        Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
        Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

        // A return is raised against an order, then lives under its own number.
        Route::get('/returns', [AccountReturnController::class, 'index'])->name('returns.index');
        Route::get('/orders/{order}/return', [AccountReturnController::class, 'create'])->name('returns.create');
        Route::post('/orders/{order}/return', [AccountReturnController::class, 'store'])->name('returns.store');
        Route::get('/returns/{return}', [AccountReturnController::class, 'show'])->name('returns.show');
        Route::delete('/returns/{return}', [AccountReturnController::class, 'destroy'])->name('returns.destroy');
    });

    /*
     * Kept so existing links and Breeze's own redirects still resolve.
     */
    Route::middleware('auth')->group(function (): void {
        Route::redirect('/profile', '/account/profile')->name('profile.edit');
    });
});
