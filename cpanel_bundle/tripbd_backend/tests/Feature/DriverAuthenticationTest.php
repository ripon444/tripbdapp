<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DriverProfile;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DriverAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_registration_creates_driver_profile_and_wallet_with_pending_status()
    {
        $response = $this->postJson('/api/v1/driver/register', [
            'name' => 'Md. Rafiqul Islam',
            'phone' => '01812345678',
            'password' => 'DriverPass123!',
            'password_confirmation' => 'DriverPass123!',
            'nid_number' => '19901234567890123',
            'driving_license_number' => 'DL-DHAKA-2023-8899',
            'address' => 'Tejgaon Truck Stand',
            'district' => 'Dhaka'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'user' => [
                    'role' => 'driver',
                    'driver_profile' => [
                        'verification_status' => 'pending',
                        'online_status' => 'offline'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('driver_profiles', [
            'nid_number' => '19901234567890123',
            'verification_status' => 'pending'
        ]);

        $this->assertDatabaseHas('wallets', [
            'balance' => 0.00
        ]);
    }

    public function test_driver_can_upload_kyc_documents_securely()
    {
        Storage::fake('local');

        $user = User::factory()->create(['role' => 'driver']);
        $driverProfile = DriverProfile::create([
            'user_id' => $user->id,
            'nid_number' => '1234567890',
            'driving_license_number' => 'DL123456',
            'address' => 'Dhaka',
            'district' => 'Dhaka'
        ]);

        $token = $user->createToken('driver_token', ['driver'])->plainTextToken;

        $file = UploadedFile::fake()->create('nid_card.jpg', 1000, 'image/jpeg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/documents', [
                'document_type' => 'nid_front',
                'file' => $file
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'document' => [
                    'document_type' => 'nid_front',
                    'verification_status' => 'pending'
                ]
            ]);

        $this->assertDatabaseHas('driver_documents', [
            'driver_id' => $driverProfile->id,
            'document_type' => 'nid_front',
            'verification_status' => 'pending'
        ]);
    }

    public function test_driver_can_register_vehicle_for_approval()
    {
        $user = User::factory()->create(['role' => 'driver']);
        $driverProfile = DriverProfile::create([
            'user_id' => $user->id,
            'nid_number' => '1234567890',
            'driving_license_number' => 'DL123456',
            'address' => 'Dhaka',
            'district' => 'Dhaka'
        ]);

        $vType = VehicleType::firstOrCreate([
            'slug' => 'pickup-1-ton'
        ], [
            'service_category_id' => 1,
            'name' => '1 Ton Pickup',
            'base_fare' => 800,
            'per_km_rate' => 45,
            'minimum_fare' => 1000
        ]);

        $token = $user->createToken('driver_token', ['driver'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/driver/vehicles', [
                'vehicle_type_id' => $vType->id,
                'registration_number' => 'DHAKA-METRO-TA-11-2233',
                'brand' => 'Tata',
                'model' => 'Ace Mega',
                'year' => 2022,
                'color' => 'Blue'
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'vehicle' => [
                    'verification_status' => 'pending'
                ]
            ]);
    }
}
