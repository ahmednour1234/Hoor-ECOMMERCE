<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();

            // Self-reference allows one level of sub-categories (Jeans > Wide Leg)
            // without committing to a full nested-set implementation.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name_ar', 120);
            $table->string('name_en', 120);
            $table->string('slug', 140)->unique();

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->string('image')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // SEO
            $table->string('meta_title_ar', 180)->nullable();
            $table->string('meta_title_en', 180)->nullable();
            $table->string('meta_description_ar', 320)->nullable();
            $table->string('meta_description_en', 320)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Storefront menus read active categories in curated order.
            $table->index(['is_active', 'sort_order']);
            $table->index(['parent_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
