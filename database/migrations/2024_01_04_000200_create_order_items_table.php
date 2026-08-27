<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            /*
             * Links back to the catalog for reporting and restocking.
             *
             * Nulled rather than restricted on delete: a product removed from
             * the catalog must not make its past orders unreadable. Everything
             * needed to render the line is snapshotted below, so a null here
             * costs the customer nothing.
             */
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            /*
             * ---------------------------------------------------------------
             * Snapshot
             * ---------------------------------------------------------------
             *
             * What the customer actually bought, frozen at the moment of sale.
             *
             * These are not a cache of the catalog — they are the record. If a
             * product is renamed, repriced, recoloured or deleted tomorrow, this
             * order must still show what was ordered and what was charged. That
             * is both a customer-trust requirement and an accounting one.
             */
            $table->string('product_name_ar', 180);
            $table->string('product_name_en', 180);
            $table->string('sku', 64);
            $table->string('color_name_ar', 60)->nullable();
            $table->string('color_name_en', 60)->nullable();
            $table->string('size_name_ar', 40)->nullable();
            $table->string('size_name_en', 40)->nullable();

            // Path only; the file may be replaced but the record survives.
            $table->string('image_path')->nullable();

            // Unit price charged, in piastres, and the pre-discount price it
            // was struck through from.
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('unit_price_before_discount');

            $table->unsignedInteger('quantity');

            // Stored rather than derived so a line always reconciles against
            // the order total, even if rounding rules ever change.
            $table->unsignedInteger('line_total');

            $table->timestamps();

            $table->index('order_id');

            // Powers "units sold" reporting and the best-selling sort.
            $table->index('product_variant_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
