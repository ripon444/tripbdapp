<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OtpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_is_hashed_and_not_stored_as_plaintext()
    {
        $service = app(OtpService::class);
        $result = $service->generateOtp('01711111111', 'registration');

        $this->assertTrue($result['success']);

        $otpRecord = Otp::where('phone', '01711111111')->first();
        $this->assertNotNull($otpRecord);

        // Check it starts with bcrypt signature $2y$
        $this->assertStringStartsWith('$2y$', $otpRecord->otp_hash);
        $this->assertNotEquals('123456', $otpRecord->otp_hash);
    }

    public function test_max_attempts_lockout_prevents_brute_force()
    {
        $otp = Otp::create([
            'phone' => '01711111111',
            'otp_hash' => Hash::make('654321'),
            'purpose' => 'phone_verification',
            'attempts' => 3,
            'max_attempts' => 3,
            'expires_at' => Carbon::now()->addMinutes(5)
        ]);

        $service = app(OtpService::class);
        $result = $service->verifyOtp('01711111111', '654321', 'phone_verification');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Maximum verification attempts exceeded', $result['message']);
    }

    public function test_expired_otp_is_rejected()
    {
        $otp = Otp::create([
            'phone' => '01711111111',
            'otp_hash' => Hash::make('654321'),
            'purpose' => 'phone_verification',
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => Carbon::now()->subMinutes(1) // Expired 1 min ago
        ]);

        $service = app(OtpService::class);
        $result = $service->verifyOtp('01711111111', '654321', 'phone_verification');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', $result['message']);
    }
}
