<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            /*
             * Human-facing reference, e.g. HOOR-2026-000042.
             *
             * Customers quote this on the phone and couriers write it on the
             * parcel, so it is generated rather than exposing the primary key.
             */
            $table->string('number', 32)->unique();

            // Guests are first class: an order need not belong to an account.
            // Nulled rather than cascaded so deleting a customer never destroys
            // the sales record.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('status', 32)->default(OrderStatus::Pending->value);
            $table->string('payment_method', 32)->default(PaymentMethod::CashOnDelivery->value);

            /*
             * Money, in piastres.
             *
             * Every figure is computed server-side at order time and then
             * frozen. A later price change in the catalog must never alter what
             * a placed order says the customer owes.
             */
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('shipping')->default(0);
            $table->unsignedInteger('total');

            /*
             * Coupon snapshot.
             *
             * The code is stored as text so the order still reads correctly if
             * the coupon is later renamed or deleted. `coupon_id` is left
             * unconstrained here because the coupons table arrives with its own
             * module — that migration adds the foreign key.
             */
            $table->unsignedBigInteger('coupon_id')->nullable();
            $table->string('coupon_code', 64)->nullable();

            $table->text('notes')->nullable();

            // Set when the order reaches its terminal states, for reporting.
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // The admin list filters by status and sorts by recency.
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
