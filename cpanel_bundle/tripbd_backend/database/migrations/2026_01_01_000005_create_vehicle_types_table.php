<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('image', 500)->nullable();
            $table->unsignedSmallInteger('passenger_capacity')->default(1);
            $table->decimal('load_capacity', 8, 2)->default(0.00)->comment('In Tons or KG');
            $table->decimal('base_fare', 10, 2)->default(0.00);
            $table->decimal('per_km_rate', 10, 2)->default(0.00);
            $table->decimal('per_hour_rate', 10, 2)->default(0.00);
            $table->decimal('minimum_fare', 10, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('service_category_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};
