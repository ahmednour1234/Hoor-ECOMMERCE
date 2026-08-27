<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_addresses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('full_name', 160);

            // Egyptian mobile numbers. Two are allowed because couriers here
            // routinely need a second contact to complete a delivery.
            $table->string('phone', 20);
            $table->string('phone_alt', 20)->nullable();

            /*
             * The destination, snapshotted alongside its identifiers.
             *
             * The names are frozen for the same reason as the order items: a
             * governorate renamed or an area deleted must not change what a
             * past delivery address said.
             */
            $table->unsignedBigInteger('governorate_id')->nullable();
            $table->string('governorate_name_ar', 120);
            $table->string('governorate_name_en', 120);

            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('area_name_ar', 140)->nullable();
            $table->string('area_name_en', 140)->nullable();

            $table->text('address');
            $table->string('landmark', 240)->nullable();

            // What was charged to reach here, frozen with the rest.
            $table->unsignedInteger('shipping_fee');

            $table->timestamps();

            // One address per order; a shipping/billing split has no meaning
            // for cash on delivery.
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
