<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The email an order's confirmation is sent to.
 *
 * Kept on the address rather than looked up from the account, for the same
 * reason the phone and the street are: most HOOR customers check out as guests
 * and have no account to look anything up from, and even a signed-in customer
 * may want a particular order's confirmation somewhere else.
 *
 * Nullable in the schema although the form requires it: orders placed before
 * this shipped have no email, and a NOT NULL column would have to invent one
 * for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_addresses', function (Blueprint $table): void {
            $table->string('email', 190)->nullable()->after('phone_alt');
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table): void {
            $table->dropColumn('email');
        });
    }
};
