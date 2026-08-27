<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Null on the opening entry, where there is no previous state.
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);

            // Who moved it. Null means the system did — order placement, or a
            // scheduled job.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('note', 500)->nullable();

            $table->timestamps();

            // History is append-only and always read in order for one order.
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
