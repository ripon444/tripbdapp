<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\ServiceCategory;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $pickupLat = 23.7 + fake()->randomFloat(4, 0.01, 0.15);
        $pickupLng = 90.3 + fake()->randomFloat(4, 0.01, 0.15);
        $destLat = 23.7 + fake()->randomFloat(4, 0.01, 0.15);
        $destLng = 90.3 + fake()->randomFloat(4, 0.01, 0.15);

        return [
            'booking_number' => 'TRIP-' . strtoupper(Str::random(8)),
            'customer_id' => User::factory(),
            'driver_id' => null,
            'vehicle_id' => null,
            'service_category_id' => 1,
            'vehicle_type_id' => 1,
            'pickup_address' => fake()->address(),
            'pickup_latitude' => $pickupLat,
            'pickup_longitude' => $pickupLng,
            'destination_address' => fake()->address(),
            'destination_latitude' => $destLat,
            'destination_longitude' => $destLng,
            'distance_km' => fake()->randomFloat(2, 3.5, 45.0),
            'estimated_duration_minutes' => fake()->numberBetween(15, 120),
            'scheduled_at' => null,
            'load_description' => 'Household goods and packages',
            'load_weight' => 500.00,
            'passenger_count' => 1,
            'luggage_count' => 2,
            'trip_type' => 'one_way',
            'estimated_fare' => fake()->randomFloat(2, 500, 4500),
            'final_fare' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'customer_notes' => fake()->sentence(),
        ];
    }
}
