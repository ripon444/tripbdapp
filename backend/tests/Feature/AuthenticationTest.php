<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_send_otp_with_valid_bangladesh_phone()
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '01711111111',
            'purpose' => 'registration'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('otps', [
            'phone' => '01711111111',
            'purpose' => 'registration'
        ]);
    }

    public function test_invalid_bangladesh_phone_is_rejected()
    {
        $response = $this->postJson('/api/v1/auth/send-otp', [
            'phone' => '12345678', // Invalid length/prefix
            'purpose' => 'registration'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_customer_registration_creates_user_and_customer_profile()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Tanvir Hasan',
            'phone' => '01712345678',
            'email' => 'tanvir@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'district' => 'Dhaka'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'token',
                'user' => ['id', 'name', 'phone', 'role', 'status']
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => '01712345678',
            'role' => 'customer',
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('customer_profiles', [
            'district' => 'Dhaka'
        ]);
    }

    public function test_duplicate_phone_number_registration_fails()
    {
        User::factory()->create(['phone' => '01712345678']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'phone' => '01712345678',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_customer_can_login_with_phone_and_password()
    {
        $user = User::factory()->create([
            'phone' => '01711111111',
            'password' => Hash::make('Secret123!')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01711111111',
            'password' => 'Secret123!'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token',
                'user' => ['id', 'name', 'phone', 'role']
            ]);
    }

    public function test_invalid_password_returns_unauthorized()
    {
        User::factory()->create([
            'phone' => '01711111111',
            'password' => Hash::make('CorrectPassword123!')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01711111111',
            'password' => 'WrongPassword!'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid mobile number/email or password.'
            ]);
    }

    public function test_password_hash_never_exposed_in_response()
    {
        $user = User::factory()->create([
            'phone' => '01711111111',
            'password' => Hash::make('Secret123!')
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '01711111111',
            'password' => 'Secret123!'
        ]);

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertArrayNotHasKey('password_hash', $response->json('user'));
    }

    public function test_authenticated_user_can_logout_and_revoke_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertCount(0, $user->tokens()->get());
    }
}
