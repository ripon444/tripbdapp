<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\DriverProfile;
use App\Models\Wallet;
use App\Models\Vehicle;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        $admin = User::where('email', 'admin@tripbd.com')
            ->orWhere('phone', '01700000000')
            ->first();

        if ($admin) {
            $admin->update([
                'name' => 'TripBD System Admin',
                'phone' => '01700000000',
                'email' => 'admin@tripbd.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);
        } else {
            $admin = User::create([
                'name' => 'TripBD System Admin',
                'phone' => '01700000000',
                'email' => 'admin@tripbd.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]);
        }

        // 2. Demo Customer (Tanvir Hasan)
        $customer = User::updateOrCreate(
            ['phone' => '01711111111'],
            [
                'name' => 'Tanvir Hasan',
                'email' => 'tanvir@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]
        );

        CustomerProfile::updateOrCreate(
            ['user_id' => $customer->id],
            [
                'address' => 'House 42, Road 11, Banani',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'country' => 'Bangladesh',
                'date_of_birth' => '1992-05-14',
            ]
        );

        // 3. Demo Driver - Truck Captain (Rafiqul Islam)
        $driverUser1 = User::updateOrCreate(
            ['phone' => '01822222222'],
            [
                'name' => 'Md. Rafiqul Islam',
                'email' => 'rafiq.driver@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'driver',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]
        );

        $driverProfile1 = DriverProfile::updateOrCreate(
            ['user_id' => $driverUser1->id],
            [
                'address' => 'Tejgaon Truck Stand, Dhaka',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'nid_number' => '19881234567890123',
                'license_number' => 'DK-88992019-PR',
                'license_expiry' => '2028-12-31',
                'verification_status' => 'approved',
                'online_status' => 'online',
                'rating' => 4.92,
                'total_trips' => 148,
            ]
        );

        Wallet::updateOrCreate(
            ['driver_id' => $driverUser1->id],
            ['balance' => 4500.00]
        );

        Vehicle::updateOrCreate(
            ['registration_number' => 'DHAKA-METRO-TA-11-2345'],
            [
                'driver_id' => $driverProfile1->id,
                'vehicle_type_id' => 1, // 1 Ton Pickup
                'vehicle_number' => 'DHAKA METRO TA 11-2345',
                'brand' => 'Tata',
                'model' => 'Ace EX2',
                'year' => 2022,
                'color' => 'Blue',
                'passenger_capacity' => 2,
                'load_capacity' => 1.00,
                'verification_status' => 'approved',
                'status' => 'active',
            ]
        );

        // 4. Demo Driver - Ambulance Paramedic (Jalal Ahmed)
        $driverUser2 = User::updateOrCreate(
            ['phone' => '01933333333'],
            [
                'name' => 'Jalal Ahmed (Ambulance)',
                'email' => 'jalal.ambulance@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'driver',
                'status' => 'active',
                'phone_verified_at' => now(),
            ]
        );

        $driverProfile2 = DriverProfile::updateOrCreate(
            ['user_id' => $driverUser2->id],
            [
                'address' => 'Square Hospital Stand, Panthapath',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'nid_number' => '19859876543210123',
                'license_number' => 'DK-77221044-EM',
                'license_expiry' => '2029-06-30',
                'verification_status' => 'approved',
                'online_status' => 'online',
                'rating' => 5.00,
                'total_trips' => 92,
            ]
        );

        Wallet::updateOrCreate(
            ['driver_id' => $driverUser2->id],
            ['balance' => 8200.00]
        );

        Vehicle::updateOrCreate(
            ['registration_number' => 'DHAKA-METRO-CHHA-71-8899'],
            [
                'driver_id' => $driverProfile2->id,
                'vehicle_type_id' => 7, // ICU Ambulance
                'vehicle_number' => 'DHAKA METRO CHHA 71-8899',
                'brand' => 'Toyota',
                'model' => 'HiAce High Roof ICU',
                'year' => 2023,
                'color' => 'White',
                'passenger_capacity' => 4,
                'load_capacity' => 0.00,
                'verification_status' => 'approved',
                'status' => 'active',
            ]
        );
    }
}
