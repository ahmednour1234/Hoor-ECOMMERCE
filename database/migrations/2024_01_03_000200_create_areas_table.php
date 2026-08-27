<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table): void {
            $table->id();

            // Restricted rather than cascading: deleting a governorate must not
            // silently destroy the areas — and therefore the addresses — under it.
            $table->foreignId('governorate_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name_ar', 140);
            $table->string('name_en', 140);

            /*
             * Null means "inherit the governorate's fee".
             *
             * This is what keeps the system manageable: an admin sets 27
             * governorate rates and only overrides the handful of areas that
             * genuinely cost more to reach.
             */
            $table->unsignedInteger('shipping_fee')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // An area name must be unique within its governorate, but the same
            // district name may legitimately recur in another.
            $table->unique(['governorate_id', 'name_en']);

            // Checkout lists a governorate's active areas in order.
            $table->index(['governorate_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
