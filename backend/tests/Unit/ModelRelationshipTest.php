<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\DriverProfile;
use App\Models\ServiceCategory;
use App\Models\VehicleType;
use App\Models\Vehicle;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\Rating;
use App\Models\Complaint;
use App\Models\PromoCode;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_has_one_customer_profile(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $profile = CustomerProfile::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(CustomerProfile::class, $user->customerProfile);
        $this->assertEquals($profile->id, $user->customerProfile->id);
    }

    /** @test */
    public function user_has_one_driver_profile_and_wallet(): void
    {
        $driverUser = User::factory()->driver()->create();
        $driverProfile = DriverProfile::factory()->create(['user_id' => $driverUser->id]);
        $wallet = Wallet::create(['driver_id' => $driverUser->id, 'balance' => 1500.00]);

        $this->assertInstanceOf(DriverProfile::class, $driverUser->driverProfile);
        $this->assertInstanceOf(Wallet::class, $driverUser->wallet);
        $this->assertEquals(1500.00, (float)$driverUser->wallet->balance);
    }

    /** @test */
    public function service_category_has_many_vehicle_types(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Ambulance Trip',
            'slug' => 'ambulance-test',
            'icon' => 'Ambulance',
            'status' => 'active'
        ]);

        $type1 = VehicleType::create([
            'service_category_id' => $category->id,
            'name' => 'ICU Ambulance',
            'slug' => 'icu-test',
            'base_fare' => 6000,
            'status' => 'active'
        ]);

        $this->assertCount(1, $category->vehicleTypes);
        $this->assertEquals($category->id, $type1->serviceCategory->id);
    }

    /** @test */
    public function booking_belongs_to_customer_and_service_category(): void
    {
        $customer = User::factory()->create();
        $category = ServiceCategory::create([
            'name' => 'Truck Trip',
            'slug' => 'truck-test',
            'icon' => 'Truck',
            'status' => 'active'
        ]);
        $type = VehicleType::create([
            'service_category_id' => $category->id,
            'name' => '1 Ton Pickup',
            'slug' => 'pickup-1t-test',
            'base_fare' => 1000,
            'status' => 'active'
        ]);

        $booking = Booking::create([
            'booking_number' => 'TRIP-TEST-1234',
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'vehicle_type_id' => $type->id,
            'pickup_address' => 'Mirpur 10, Dhaka',
            'destination_address' => 'Motijheel, Dhaka',
            'distance_km' => 14.5,
            'estimated_fare' => 1200.00,
            'status' => 'pending',
        ]);

        $this->assertEquals($customer->id, $booking->customer->id);
        $this->assertEquals('Truck Trip', $booking->serviceCategory->name);
        $this->assertEquals('1 Ton Pickup', $booking->vehicleType->name);
    }

    /** @test */
    public function system_setting_helper_methods_work(): void
    {
        SystemSetting::set('test_commission', 12.50, 'decimal', 'Test commission description');

        $this->assertEquals(12.50, SystemSetting::get('test_commission'));
    }
}
