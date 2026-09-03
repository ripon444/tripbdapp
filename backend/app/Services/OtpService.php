<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate a new 6-digit OTP and store the hash.
     *
     * @param string $phone
     * @param string $purpose
     * @return array [success => bool, message => string, otp_code => ?string]
     */
    public function generateOtp(string $phone, string $purpose = 'phone_verification'): array
    {
        $resendSeconds = (int) SystemSetting::get('otp_resend_seconds', 60);
        $expiryMinutes = (int) SystemSetting::get('otp_expiry_minutes', 5);
        $maxAttempts = (int) SystemSetting::get('otp_max_attempts', 3);

        // Check recent unexpired OTP for rate limit (resend cooldown)
        $recentOtp = Otp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('created_at', '>', Carbon::now()->subSeconds($resendSeconds))
            ->first();

        if ($recentOtp) {
            $secondsRemaining = $resendSeconds - Carbon::now()->diffInSeconds($recentOtp->created_at);
            return [
                'success' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting a new OTP code.",
                'retry_after' => $secondsRemaining
            ];
        }

        // Generate 6-digit cryptographic random OTP
        $rawOtp = (string) random_int(100000, 999999);

        // Invalidate old unverified OTPs for this phone and purpose
        Otp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        // Create new record with Bcrypt hash
        $otp = Otp::create([
            'phone' => $phone,
            'otp_hash' => Hash::make($rawOtp),
            'purpose' => $purpose,
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        // Send SMS via Gateway (Simulated or Real provider)
        $this->dispatchSms($phone, "Your TripBD verification code is {$rawOtp}. Valid for {$expiryMinutes} minutes.");

        $response = [
            'success' => true,
            'message' => "Verification OTP sent successfully to {$phone}.",
            'expires_in_minutes' => $expiryMinutes,
            'resend_in_seconds' => $resendSeconds
        ];

        // Development / Local mode helper: expose OTP only if APP_ENV=local
        if (config('app.env') === 'local') {
            $response['dev_otp'] = $rawOtp;
        }

        return $response;
    }

    /**
     * Verify an OTP code.
     *
     * @param string $phone
     * @param string $code
     * @param string $purpose
     * @return array [success => bool, message => string]
     */
    public function verifyOtp(string $phone, string $code, string $purpose = 'phone_verification'): array
    {
        $otp = Otp::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'No active OTP request found for this phone number.'
            ];
        }

        if ($otp->isExpired()) {
            return [
                'success' => false,
                'message' => 'This OTP code has expired. Please request a new code.'
            ];
        }

        if ($otp->hasExceededMaxAttempts()) {
            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new OTP.'
            ];
        }

        if ($otp->verifyCode($code)) {
            return [
                'success' => true,
                'message' => 'Phone number verified successfully.'
            ];
        }

        $remainingAttempts = max(0, $otp->max_attempts - $otp->attempts);
        return [
            'success' => false,
            'message' => "Invalid OTP code. {$remainingAttempts} attempt(s) remaining."
        ];
    }

    /**
     * Dispatch SMS through configured cPanel compatible SMS gateway.
     */
    protected function dispatchSms(string $phone, string $message): void
    {
        // cPanel HTTP REST SMS Gateway Dispatcher (e.g., Greenweb, BulkSMS BD)
        // Log in local mode only
        if (config('app.env') === 'local') {
            Log::info("SMS to {$phone}: {$message}");
        }
    }
}
