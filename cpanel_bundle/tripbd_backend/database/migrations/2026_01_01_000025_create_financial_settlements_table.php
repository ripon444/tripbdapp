<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->string('settlement_reference', 50)->unique();
            $table->decimal('gross_fare', 12, 2);
            $table->decimal('platform_commission_percent', 5, 2);
            $table->decimal('platform_commission_amount', 12, 2);
            $table->decimal('driver_earning_amount', 12, 2);
            $table->decimal('service_charge_amount', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'processing', 'settled', 'reversed', 'failed'])->default('pending');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('settlement_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_settlements');
    }
};
