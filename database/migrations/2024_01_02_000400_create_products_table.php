<?php

declare(strict_types=1);

use App\Enums\ProductStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();

            // Restricted rather than cascading: deleting a category must never
            // silently delete the products filed under it.
            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name_ar', 180);
            $table->string('name_en', 180);
            $table->string('slug', 200)->unique();

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            // Short teaser used on cards and meta descriptions.
            $table->string('short_description_ar', 320)->nullable();
            $table->string('short_description_en', 320)->nullable();

            /*
             * Money is stored as an unsigned integer number of piastres
             * (1 EGP = 100). Integers keep every subtotal, discount and total
             * exact, which floating point cannot guarantee.
             *
             * These are the product-level defaults; a variant may override
             * either value when a colour or size is priced differently.
             */
            $table->unsignedInteger('base_price');
            $table->unsignedInteger('sale_price')->nullable();

            $table->string('status', 20)->default(ProductStatus::Draft->value);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);

            // Denim-specific descriptive attributes shown on the product page.
            $table->string('fabric_ar', 120)->nullable();
            $table->string('fabric_en', 120)->nullable();
            $table->string('care_ar', 240)->nullable();
            $table->string('care_en', 240)->nullable();

            // SEO
            $table->string('meta_title_ar', 180)->nullable();
            $table->string('meta_title_en', 180)->nullable();
            $table->string('meta_description_ar', 320)->nullable();
            $table->string('meta_description_en', 320)->nullable();

            // Set when a product first becomes visible, so "new in" listings do
            // not depend on created_at (which changes with data imports).
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            /*
             * Storefront listings filter on status and then sort, so the
             * composite indexes below match the real query shapes:
             *   WHERE status = ? AND category_id = ? ORDER BY ...
             *   WHERE status = ? AND is_featured = 1
             */
            $table->index(['status', 'category_id']);
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_new']);
            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
