<?php

namespace Database\Factories;

use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverProfileFactory extends Factory
{
    protected $model = DriverProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'profile_photo' => null,
            'address' => fake()->streetAddress(),
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'nid_number' => fake()->numerify('198##########'),
            'license_number' => 'DK-' . fake()->numerify('########-PR'),
            'license_expiry' => fake()->dateTimeBetween('+1 year', '+5 years')->format('Y-m-d'),
            'verification_status' => 'approved',
            'online_status' => 'online',
            'rating' => fake()->randomFloat(2, 4.2, 5.0),
            'total_trips' => fake()->numberBetween(10, 500),
        ];
    }
}
