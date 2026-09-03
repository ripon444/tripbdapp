<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('rating')->comment('1 to 5 stars');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('driver_id');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
