<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colors', function (Blueprint $table): void {
            $table->id();

            $table->string('name_ar', 60);
            $table->string('name_en', 60);
            $table->string('slug', 80)->unique();

            // Swatch rendered in the colour selector. Stored with the leading
            // '#' so it can be dropped straight into a style attribute.
            $table->char('hex', 7);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colors');
    }
};
