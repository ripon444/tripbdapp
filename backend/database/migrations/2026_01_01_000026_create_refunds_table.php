<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->string('refund_reference', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('type', ['full', 'partial'])->default('full');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'processed', 'rejected', 'failed'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('gateway_refund_id', 100)->nullable();
            $table->timestamps();

            $table->index('payment_id');
            $table->index('booking_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('refund_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
