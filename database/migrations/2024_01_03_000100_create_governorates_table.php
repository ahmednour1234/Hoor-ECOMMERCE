<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table): void {
            $table->id();

            $table->string('name_ar', 120);
            $table->string('name_en', 120);
            $table->string('code', 10)->unique();

            /*
             * The delivery fee for this governorate, in piastres.
             *
             * Held here rather than in a separate rates table because the rule
             * is one fee per destination: a rates table would be a 1:1 join
             * carrying no additional information. Areas may override it.
             */
            $table->unsignedInteger('shipping_fee')->default(0);

            // Working days, shown to the customer at checkout.
            $table->unsignedTinyInteger('delivery_days_min')->default(2);
            $table->unsignedTinyInteger('delivery_days_max')->default(5);

            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // Checkout reads active governorates in display order.
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governorates');
    }
};
