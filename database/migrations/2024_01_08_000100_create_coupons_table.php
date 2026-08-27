<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount codes, and the record of who redeemed them.
 *
 * Money is stored in piastres throughout, matching orders and products: a
 * percentage coupon's cap and a fixed coupon's value are both integers, so no
 * float ever touches a total.
 *
 * Redemptions are their own table rather than a counter on the coupon. A
 * counter can say "used 40 times" but not "used by this customer already",
 * and a per-customer limit is the usual shape of a welcome code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();

            // Compared upper-case, so casing never matters to a customer
            // typing one in off a printed card.
            $table->string('code', 64)->unique();

            $table->string('name_ar', 120)->nullable();
            $table->string('name_en', 120)->nullable();

            // CouponType: fixed | percentage
            $table->string('type', 20);

            /*
             * What the coupon is worth.
             *
             * For a fixed coupon this is piastres off; for a percentage it is
             * whole percent (1–100). One column because a coupon is only ever
             * one of the two, and two nullable columns would allow a row that
             * is neither or both.
             */
            $table->unsignedInteger('value');

            /*
             * The ceiling on a percentage discount, in piastres.
             *
             * "20% off, up to 100 EGP" is the ordinary shape of a promotion —
             * without it, a percentage on a large basket gives away more than
             * the campaign intended. Meaningless for a fixed coupon, so null.
             */
            $table->unsignedInteger('max_discount')->nullable();

            // The basket must reach this before the code applies, in piastres.
            $table->unsignedInteger('min_order')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            /*
             * How many redemptions in total, and how many per customer.
             *
             * Null means unlimited in both cases. Kept apart because they
             * answer different questions: a campaign capped at 500 uses is not
             * the same as one every customer may use once.
             */
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable()->default(1);

            /*
             * Denormalised count of redemptions.
             *
             * The redemptions table is the source of truth; this exists so the
             * admin listing can show usage without a subquery per row, and is
             * recomputed rather than trusted when a limit is enforced.
             */
            $table->unsignedInteger('used_count')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();

            /*
             * The order the discount was actually taken on.
             *
             * Nullable on delete rather than cascade: a redemption is a record
             * that the code was used, and losing the order should not quietly
             * hand the customer another use.
             */
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            // Set when she was signed in.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            /*
             * The phone the order was placed with, normalised.
             *
             * The per-customer key for a shop that sells to guests: most HOOR
             * customers never register, and an account-only limit would let a
             * one-per-customer code be reused by simply not logging in. Every
             * COD order carries a validated phone, so this always exists.
             */
            $table->string('phone', 20)->nullable();

            // What the discount was worth, in piastres, at the time.
            $table->unsignedInteger('discount');

            $table->timestamps();

            // The two lookups the limit checks make.
            $table->index(['coupon_id', 'phone']);
            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
