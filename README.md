# HOOR — حور

An e-commerce storefront for an Egyptian modest denim label, built as a Laravel
monolith.

Arabic and English throughout, right-to-left and left-to-right, prices in EGP,
and **cash on delivery only** — there is no payment gateway, by design.

## Stack

Laravel 12 · PHP 8.2+ · Blade · Alpine.js · Tailwind CSS · Vite · MySQL
(SQLite in development)

No React, no Vue, no SPA. Business logic lives in services and actions;
controllers stay thin.

## Getting started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Development uses SQLite; create the file the .env points at.
touch database/database.sqlite
php artisan migrate --seed

# Product images are served from storage.
php artisan storage:link

npm run build
php artisan serve
```

The seeders create a working catalog, the 27 Egyptian governorates with
shipping fees, hero slides, FAQs and an administrator:

```
admin@hoor.eg / ChangeMe!2026
```

Change that password before deploying anywhere.

## What is here

**Storefront** — homepage, shop with server-side filtering and shareable
filter URLs, product pages with size/colour variant selection, a session cart
that needs no account, cash-on-delivery checkout, and public order tracking by
order number plus phone.

**Customer account** — optional throughout. Profile, saved addresses, order
history, wishlist, and returns. Registration is never required to buy or to
track an order.

**Back office** — products and variants, categories, inventory, orders through
a ten-status workflow, returns and exchanges, coupons, Egyptian shipping
destinations, storefront content and site settings, and a dashboard reporting
on real figures.

## Decisions worth knowing

**Money is stored as integer piastres.** `App\Casts\Money` converts at the
boundary. Floats cannot represent decimal currency exactly, and a shop that
rounds differently in two places has a bug it will not find for months.

**Stock is tracked per variant, never per product.** A product's availability
is derived from its variants; there is no product-level stock column to drift
out of step.

**Orders snapshot what was bought.** Name, SKU, size, colour and price are
copied onto the order item at checkout, so renaming a product or changing its
price never rewrites history.

**Totals are calculated server-side, always.** The browser submits a coupon
code, never an amount. `CouponService` is the single place a discount is
judged, so the figure quoted in the cart is the figure charged at checkout.

**Bilingual content uses sibling columns** (`name_ar` / `name_en`) rather than
a translations table, read through a `HasTranslations` trait. One query, no
joins, and a missing translation is visible rather than silently empty.

**Stock is locked in ascending id order** wherever it moves — placing an
order, cancelling one, receiving a return — so concurrent operations cannot
deadlock against each other.

## Tests

```bash
php artisan test
```

Around 525 feature tests. Several are query-budget tests that assert the query
count does not grow with the size of the data, which is how N+1 regressions
get caught here rather than in production.

## Licence

Proprietary. All rights reserved.
