<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('profile_photo', 500)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('nid_number', 50);
            $table->string('license_number', 50);
            $table->date('license_expiry');
            $table->enum('verification_status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->enum('online_status', ['online', 'offline', 'busy'])->default('offline');
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->unsignedInteger('total_trips')->default(0);
            $table->timestamps();

            $table->index('verification_status');
            $table->index('online_status');
            $table->index('district');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_profiles');
    }
};
