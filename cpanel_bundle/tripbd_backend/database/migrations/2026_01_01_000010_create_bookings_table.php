<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number', 50)->unique();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('cascade');

            $table->text('pickup_address');
            $table->decimal('pickup_latitude', 10, 8)->nullable();
            $table->decimal('pickup_longitude', 11, 8)->nullable();

            $table->text('destination_address');
            $table->decimal('destination_latitude', 10, 8)->nullable();
            $table->decimal('destination_longitude', 11, 8)->nullable();

            $table->decimal('distance_km', 8, 2)->default(0.00);
            $table->unsignedInteger('estimated_duration_minutes')->default(0);

            $table->dateTime('scheduled_at')->nullable();

            $table->text('load_description')->nullable();
            $table->decimal('load_weight', 8, 2)->nullable();

            $table->unsignedSmallInteger('passenger_count')->default(1);
            $table->unsignedSmallInteger('luggage_count')->default(0);

            $table->enum('trip_type', ['one_way', 'round_trip', 'return_trip', 'hourly'])->default('one_way');

            $table->decimal('estimated_fare', 10, 2)->default(0.00);
            $table->decimal('final_fare', 10, 2)->nullable();

            $table->enum('status', [
                'pending',
                'searching_driver',
                'driver_assigned',
                'driver_arriving',
                'arrived',
                'loading',
                'trip_started',
                'trip_completed',
                'cancelled_by_customer',
                'cancelled_by_driver',
                'cancelled_by_admin'
            ])->default('pending');

            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            $table->text('customer_notes')->nullable();
            $table->text('driver_notes')->nullable();

            $table->enum('cancelled_by', ['customer', 'driver', 'admin'])->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('booking_number');
            $table->index('customer_id');
            $table->index('driver_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('trip_type');
            $table->index('scheduled_at');
            $table->index(['pickup_latitude', 'pickup_longitude']);
            $table->index(['destination_latitude', 'destination_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
