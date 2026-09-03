<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DriverProfile;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_admin_credentials()
    {
        $admin = User::create([
            'name' => 'TripBD Admin',
            'email' => 'admin@tripbd.com',
            'phone' => '01700000000',
            'password' => Hash::make('AdminSecret123!'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'login' => 'admin@tripbd.com',
            'password' => 'AdminSecret123!'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'role' => 'admin'
                ]
            ]);

        $this->assertDatabaseHas('admin_logs', [
            'admin_id' => $admin->id,
            'action' => 'login'
        ]);
    }

    public function test_customer_credentials_fail_on_admin_login_endpoint()
    {
        User::create([
            'name' => 'Regular Customer',
            'email' => 'cust@tripbd.com',
            'phone' => '01711111111',
            'password' => Hash::make('CustPass123!'),
            'role' => 'customer',
            'status' => 'active'
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'login' => 'cust@tripbd.com',
            'password' => 'CustPass123!'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid administrative credentials.'
            ]);
    }

    public function test_admin_can_approve_driver_kyc()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $driverUser = User::factory()->create(['role' => 'driver']);
        $driverProfile = DriverProfile::create([
            'user_id' => $driverUser->id,
            'nid_number' => '1234567890',
            'driving_license_number' => 'DL9999',
            'address' => 'Dhaka',
            'district' => 'Dhaka',
            'verification_status' => 'pending'
        ]);

        $token = $admin->createToken('admin_token', ['admin'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/admin/drivers/{$driverProfile->id}/verify", [
                'action' => 'approve'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'driver' => [
                    'verification_status' => 'approved'
                ]
            ]);

        $this->assertDatabaseHas('driver_profiles', [
            'id' => $driverProfile->id,
            'verification_status' => 'approved'
        ]);
    }
}
