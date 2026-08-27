<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table): void {
            $table->id();

            // Sizes are shown as-is in both languages (XS, S, M …), but a
            // separate Arabic label leaves room for numeric EU sizing later.
            $table->string('name_ar', 40);
            $table->string('name_en', 40);
            $table->string('code', 20)->unique();

            // XS < S < M < L < XL < XXL cannot be derived alphabetically, so the
            // intended order is stored explicitly and every listing sorts by it.
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sizes');
    }
};
