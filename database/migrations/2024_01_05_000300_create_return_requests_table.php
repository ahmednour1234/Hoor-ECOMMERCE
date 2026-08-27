<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Returns and exchanges.
 *
 * A request is raised against a delivered order and names the specific lines
 * being sent back — a customer who ordered three pieces and wants to return
 * one must be able to say so.
 *
 * The request is kept apart from the order's own status: an order stays
 * "delivered" while a return is under review, because that is what happened.
 * Only an approved return moves the order to Returned, through the same
 * Phase 10 action that handles the restock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table): void {
            $table->id();

            // Public reference, like an order number: quoted over the phone and
            // never a sequential id.
            $table->string('number', 32)->unique();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Null for a guest: returns are opened from the tracking page too,
            // where the order number and phone are the credential.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type', 20);      // ReturnType
            $table->string('status', 20);    // ReturnStatus
            $table->string('reason', 40);    // ReturnReason

            // The customer's own words, and the staff's reply.
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            // Who decided, and when. Null while pending.
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('return_request_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();

            /*
             * The order line being returned.
             *
             * Restricted rather than cascading: losing the line would leave a
             * return referring to nothing, and order items are never deleted
             * in the ordinary course of business.
             */
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();

            // Part of a line may come back, so quantity is per request, not
            // inherited from the order item.
            $table->unsignedInteger('quantity');

            $table->timestamps();

            $table->unique(['return_request_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
    }
};
