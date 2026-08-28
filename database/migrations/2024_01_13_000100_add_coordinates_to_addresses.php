<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the parcel actually goes.
 *
 * A written address in Egypt is often a description rather than a location —
 * "the building behind the pharmacy, third floor" — so a pin the customer drops
 * herself saves the courier a phone call. It supplements the address; it never
 * replaces it, because a courier reads the street name and the landmark too.
 *
 * Stored as decimals rather than floats: latitude and longitude are exact
 * values, and a float would introduce rounding into a figure that has to
 * survive a round trip to a map. 7 decimal places is about a centimetre, which
 * is far beyond what a phone's GPS can offer.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_addresses', 'customer_addresses'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->decimal('latitude', 10, 7)->nullable()->after('landmark');
                $blueprint->decimal('longitude', 10, 7)->nullable()->after('latitude');
            });
        }
    }

    public function down(): void
    {
        foreach (['order_addresses', 'customer_addresses'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn(['latitude', 'longitude']);
            });
        }
    }
};
