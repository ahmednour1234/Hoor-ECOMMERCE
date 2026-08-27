<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront content the admin manages: hero slides, promotional banners, and
 * the messages customers send.
 *
 * These are tables rather than settings rows because each has its own
 * lifecycle — an order, an image, a date range, a read/unread state — and none
 * of them is singular.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Hero slides.
         *
         * Copy is stored bilingually and rendered as live text over the
         * photograph rather than baked into the image, so it translates, is
         * indexable, and can be edited without redrawing artwork.
         */
        Schema::create('hero_slides', function (Blueprint $table): void {
            $table->id();

            $table->string('image_path');

            // Sampled from the photograph so the letterbox around a wide plate
            // matches its edges instead of showing a hard seam.
            $table->string('backdrop', 9)->nullable();

            $table->string('eyebrow_ar', 120)->nullable();
            $table->string('eyebrow_en', 120)->nullable();
            $table->string('headline_ar', 190)->nullable();
            $table->string('headline_en', 190)->nullable();
            $table->string('subheadline_ar', 255)->nullable();
            $table->string('subheadline_en', 255)->nullable();

            $table->string('cta_label_ar', 80)->nullable();
            $table->string('cta_label_en', 80)->nullable();
            $table->string('cta_url', 255)->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        /*
         * Promotional banners.
         *
         * Placement-keyed so the same table serves the strip above the header,
         * the mid-homepage panel, and anywhere added later, without a migration
         * each time.
         */
        Schema::create('banners', function (Blueprint $table): void {
            $table->id();

            $table->string('placement', 40);

            $table->string('image_path')->nullable();
            $table->string('title_ar', 190)->nullable();
            $table->string('title_en', 190)->nullable();
            $table->string('body_ar', 255)->nullable();
            $table->string('body_en', 255)->nullable();

            $table->string('cta_label_ar', 80)->nullable();
            $table->string('cta_label_en', 80)->nullable();
            $table->string('cta_url', 255)->nullable();

            /*
             * A run of dates, both optional.
             *
             * A sale banner should stop showing by itself at midnight rather
             * than relying on someone remembering to switch it off.
             */
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['placement', 'is_active', 'position']);
        });

        /*
         * Contact messages.
         *
         * Stored rather than emailed: an inbox nobody has configured SMTP for
         * is a contact form that silently fails, and a message that only ever
         * existed in an email cannot be searched or counted later.
         */
        Schema::create('contact_messages', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 160);
            $table->string('email', 190)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('subject', 190)->nullable();
            $table->text('body');

            // Set when a customer writes while signed in, so staff can see the
            // account without matching on an email address.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['read_at', 'created_at']);
        });

        /*
         * Newsletter subscribers.
         *
         * Unsubscribing sets a timestamp rather than deleting the row: the same
         * address signing up again is a different event from one that never
         * left, and a deleted row cannot tell them apart.
         */
        Schema::create('newsletter_subscribers', function (Blueprint $table): void {
            $table->id();

            $table->string('email', 190)->unique();
            $table->string('locale', 5)->nullable();

            $table->timestamp('unsubscribed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('hero_slides');
    }
};
