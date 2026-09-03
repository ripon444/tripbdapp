<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\DriverProfile;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'driver_id' => DriverProfile::factory(),
            'vehicle_type_id' => VehicleType::inRandomOrder()->first()?->id ?? 1,
            'vehicle_number' => 'DHAKA-METRO-' . fake()->randomElement(['TA', 'GA', 'CHA', 'KHA']) . '-' . fake()->numerify('##-####'),
            'brand' => fake()->randomElement(['Toyota', 'Tata', 'Mahindra', 'Isuzu', 'Honda']),
            'model' => fake()->randomElement(['Ace', 'HiAce', 'Axio', 'Bolero', 'Elf']),
            'year' => fake()->numberBetween(2018, 2024),
            'color' => fake()->randomElement(['White', 'Blue', 'Silver', 'Black', 'Yellow']),
            'passenger_capacity' => 4,
            'load_capacity' => 1.5,
            'registration_number' => 'REG-' . fake()->unique()->numerify('BD######'),
            'verification_status' => 'approved',
            'status' => 'active',
        ];
    }
}
