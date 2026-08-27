<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Completes the returns model for a fashion store.
 *
 * Three changes, each with a reason:
 *
 *   1. `received` joins the lifecycle. Approving a return is a promise; the
 *      parcel physically arriving is a separate event, and it is the one that
 *      should move stock. Without it, approving a return that never comes back
 *      inflates the shelf.
 *
 *   2. Return items gain a replacement variant. An exchange is "this one back,
 *      that one out" — a request that cannot name the replacement size or
 *      colour is not an exchange request.
 *
 *   3. `pending` becomes `requested`, matching the language the business uses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table): void {
            // When the parcel actually came back, distinct from when it was
            // approved.
            $table->timestamp('received_at')->nullable()->after('decided_at');
            $table->foreignId('received_by')->nullable()->after('received_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('return_request_items', function (Blueprint $table): void {
            /*
             * The variant the customer wants instead.
             *
             * Null for a plain return, and null-on-delete rather than cascade:
             * losing a retired variant must not delete the record of the
             * exchange that was agreed.
             */
            $table->foreignId('replacement_variant_id')->nullable()->after('order_item_id')
                ->constrained('product_variants')->nullOnDelete();

            /*
             * Snapshotted alongside it, exactly as order items are.
             *
             * A variant renamed or retired must not change what this exchange
             * said at the time it was agreed.
             */
            $table->string('replacement_sku', 100)->nullable()->after('replacement_variant_id');
            $table->string('replacement_size_ar', 60)->nullable()->after('replacement_sku');
            $table->string('replacement_size_en', 60)->nullable()->after('replacement_size_ar');
            $table->string('replacement_color_ar', 60)->nullable()->after('replacement_size_en');
            $table->string('replacement_color_en', 60)->nullable()->after('replacement_color_ar');

            // How many of this line actually came back, filled on receipt.
            $table->unsignedInteger('received_quantity')->nullable()->after('quantity');
        });

        // 'pending' is what the code called it; 'requested' is what the brief
        // and the business call it.
        DB::table('return_requests')->where('status', 'pending')->update(['status' => 'requested']);
    }

    public function down(): void
    {
        DB::table('return_requests')->where('status', 'requested')->update(['status' => 'pending']);

        Schema::table('return_request_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('replacement_variant_id');

            $table->dropColumn([
                'replacement_sku',
                'replacement_size_ar', 'replacement_size_en',
                'replacement_color_ar', 'replacement_color_en',
                'received_quantity',
            ]);
        });

        Schema::table('return_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn('received_at');
        });
    }
};
