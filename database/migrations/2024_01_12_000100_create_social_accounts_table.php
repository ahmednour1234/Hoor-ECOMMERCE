<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts linked from an outside provider.
 *
 * A table rather than columns on `users`, because one customer may link more
 * than one provider — Google today, Apple or Facebook later — and a pair of
 * `google_id` / `facebook_id` columns would mean a migration for each.
 *
 * The password column is made nullable in the same migration: a customer who
 * only ever signs in with Google has no password, and inventing a random one
 * for her would be worse than admitting she has none. It would look like a
 * credential she could reset into, and any code checking "has a password"
 * would be wrong about her.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 'google' today; the column exists so adding another provider is
            // a row, not a migration.
            $table->string('provider', 40);

            // The provider's own identifier for this person. Not the email:
            // people change their email address, and the id is what actually
            // identifies the account at the far end.
            $table->string('provider_id', 191);

            // What the provider told us at the time, for support enquiries.
            $table->string('email', 191)->nullable();
            $table->string('name', 191)->nullable();
            $table->string('avatar', 512)->nullable();

            $table->timestamps();

            // One provider account links to exactly one user.
            $table->unique(['provider', 'provider_id']);

            // And a user links each provider at most once.
            $table->unique(['user_id', 'provider']);
        });

        /*
         * SQLite cannot alter a column in place, so the change is expressed
         * through the schema builder, which rebuilds the table. Doctrine DBAL
         * is not required for this in Laravel 11+.
         */
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');

        // Not reversed: rows with a null password would violate the old
        // constraint, and there is no honest value to backfill them with.
    }
};
