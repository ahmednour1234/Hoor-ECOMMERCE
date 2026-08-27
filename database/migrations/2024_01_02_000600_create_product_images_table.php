<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Optional link to a variant so the gallery can swap when a shopper
             * picks a colour. Nulled rather than cascaded on delete: removing a
             * variant should not destroy usable product photography.
             */
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('path');

            // Bilingual alt text keeps the catalog accessible in both languages.
            $table->string('alt_ar', 180)->nullable();
            $table->string('alt_en', 180)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            /*
             * Exactly one image per product should carry this flag. It is a
             * plain boolean rather than a products.primary_image_id column so
             * that the gallery stays a single ordered list; ImageService keeps
             * the flag exclusive when images are added or reordered.
             */
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            // Galleries always read one product's images in curated order.
            $table->index(['product_id', 'sort_order']);

            // Resolving a product's primary image is a hot path on every card.
            $table->index(['product_id', 'is_primary']);

            $table->index(['product_variant_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
