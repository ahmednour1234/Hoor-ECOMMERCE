<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frequently asked questions.
 *
 * A table rather than settings rows: these are a repeating list with an order
 * and an active flag, and the shop adds one the day customers start asking it.
 * Grouped by placement so the same table can serve the contact page and a help
 * centre later without a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();

            // Where it appears; 'contact' is the only placement today.
            $table->string('placement', 40)->default('contact');

            $table->string('question_ar', 255);
            $table->string('question_en', 255);

            $table->text('answer_ar');
            $table->text('answer_en');

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['placement', 'is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
