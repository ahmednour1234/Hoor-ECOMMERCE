<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's saved address book.
 *
 * Deliberately separate from `order_addresses`, which is a snapshot frozen at
 * checkout. This table is live and editable: correcting a typo here must not
 * rewrite where a past order was delivered, and the two answer different
 * questions — "where do you live now" versus "where did this parcel go".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // A short name the customer chooses: "Home", "Work", "Mum's".
            $table->string('label', 60)->nullable();

            $table->string('full_name', 160);
            $table->string('phone', 20);
            $table->string('phone_alt', 20)->nullable();

            /*
             * Live references, unlike the order snapshot.
             *
             * Restricted on delete so an admin cannot quietly orphan a saved
             * address by removing a governorate that is still in use; the
             * shipping module deactivates destinations rather than deleting
             * them.
             */
            $table->foreignId('governorate_id')->constrained()->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();

            $table->text('address');
            $table->string('landmark', 190)->nullable();

            // Exactly one default per customer, enforced in the application
            // layer where the "unset the others" write can share a transaction.
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
