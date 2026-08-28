<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many times a reset code has been guessed.
 *
 * A six-digit code has a million combinations, which sounds like plenty until
 * you consider that a script can try them all. Rate limiting by IP is not
 * enough on its own — an attacker can rotate addresses — so the count is kept
 * against the code itself and the code is destroyed once it is exhausted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->unsignedTinyInteger('attempts')->default(0)->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table): void {
            $table->dropColumn('attempts');
        });
    }
};
