<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Small global site settings, as key-value rows.
 *
 * A table rather than a column per setting: these arrive one at a time as the
 * business thinks of them, and a migration per new phone number is not a
 * sustainable way to run a shop. The whole table is a few dozen rows, read on
 * every page, so it is loaded once and cached rather than queried per lookup.
 *
 * Anything with its own lifecycle — a slide with an image and an order, a
 * banner with a date range — gets a real table instead. This is for the
 * singular values: one phone number, one Instagram URL, one meta description.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            // Dot-namespaced, e.g. contact.whatsapp, seo.home.title_ar.
            $table->string('key', 120)->primary();

            // Null is meaningful: "set, but deliberately empty" differs from
            // "never configured", which is what falls back to the default.
            $table->text('value')->nullable();

            /*
             * How to read the value back.
             *
             * Everything is stored as text; this says what it means, so a
             * boolean toggle does not come back as the string "0" and start
             * evaluating as true.
             */
            $table->string('type', 20)->default('string');

            // Groups the admin form into panels without hardcoding the list.
            $table->string('group', 60)->default('general');

            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
