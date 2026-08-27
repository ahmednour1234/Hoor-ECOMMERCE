<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();

            // A variant has no meaning without its product, so it is removed
            // with it. Products themselves are soft-deleted, so this only fires
            // on a genuine hard delete.
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Colour and size are nullable because not every product is a full
             * two-axis matrix — a belt has colours but no sizes.
             *
             * Restricting deletion protects existing variants: a colour that is
             * in use must be deactivated rather than deleted.
             */
            $table->foreignId('color_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('size_id')->nullable()->constrained()->restrictOnDelete();

            $table->string('sku', 64)->unique();

            /*
             * THE source of truth for availability. Product-level stock is
             * always derived by summing these, so the two can never disagree.
             */
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedSmallInteger('low_stock_threshold')->default(3);

            // Null means "inherit the product price"; set only when this
            // combination genuinely costs something different.
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('sale_price')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            /*
             * A product must not carry two variants for the same combination.
             *
             * SQL treats NULLs as distinct in unique indexes, so this constraint
             * fully protects the common case where both axes are present. The
             * partially-null case (colour-only or size-only products) is guarded
             * in the application by ProductVariant::hasCombination(), because
             * enforcing it in-engine would require generated sentinel columns
             * that MySQL and SQLite express differently.
             */
            $table->unique(['product_id', 'color_id', 'size_id'], 'product_variants_combination_unique');

            // Storefront reads active, in-stock variants for a product.
            $table->index(['product_id', 'is_active']);

            // Powers the admin "low stock" report without a table scan.
            $table->index(['is_active', 'stock_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
