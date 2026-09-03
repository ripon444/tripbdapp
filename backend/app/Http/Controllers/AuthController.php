<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\Wallet;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP for phone verification or login.
     * POST /api/v1/auth/send-otp
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'purpose' => ['nullable', 'in:registration,login,password_reset,phone_verification']
        ], [
            'phone.regex' => 'Please provide a valid Bangladeshi phone number (e.g. 01711111111).'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = $request->phone;
        $purpose = $request->purpose ?? 'registration';

        $result = $this->otpService->generateOtp($phone, $purpose);

        return response()->json($result, $result['success'] ? 200 : 429);
    }

    /**
     * Verify OTP code.
     * POST /api/v1/auth/verify-otp
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'otp_code' => ['required', 'string', 'size:6'],
            'purpose' => ['nullable', 'in:registration,login,password_reset,phone_verification']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = $request->phone;
        $code = $request->otp_code;
        $purpose = $request->purpose ?? 'registration';

        $result = $this->otpService->verifyOtp($phone, $code, $purpose);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        // If user already exists, update phone_verified_at and return token if login purpose
        $user = User::where('phone', $phone)->first();
        if ($user) {
            $user->update(['phone_verified_at' => Carbon::now()]);
            if ($purpose === 'login') {
                $token = $user->createToken('tripbd_token', [$user->role])->plainTextToken;
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => $user->only(['id', 'name', 'phone', 'email', 'role', 'status', 'avatar'])
                ]);
            }
        }

        return response()->json($result, 200);
    }

    /**
     * Customer registration.
     * POST /api/v1/auth/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'unique:users,phone', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'unique:users,email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'otp_code' => ['nullable', 'string', 'size:6']
        ], [
            'phone.unique' => 'This mobile number is already registered on TripBD.',
            'phone.regex' => 'Please provide a valid 11-digit Bangladeshi mobile number.',
            'password.min' => 'Password must be at least 8 characters long.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify OTP if supplied
        if ($request->filled('otp_code')) {
            $otpCheck = $this->otpService->verifyOtp($request->phone, $request->otp_code, 'registration');
            if (!$otpCheck['success']) {
                return response()->json($otpCheck, 400);
            }
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'customer',
                'status' => 'active',
                'phone_verified_at' => $request->filled('otp_code') ? Carbon::now() : null,
            ]);

            // Create customer profile
            CustomerProfile::create([
                'user_id' => $user->id,
                'address' => $request->address ?? null,
                'district' => $request->district ?? 'Dhaka',
            ]);

            DB::commit();

            $token = $user->createToken('tripbd_customer_token', ['customer'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'phone_verified' => !is_null($user->phone_verified_at)
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * User Login (Phone or Email + Password).
     * POST /api/v1/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $login = $request->login;
        $user = User::where('phone', $login)
            ->orWhere('email', $login)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mobile number/email or password.'
            ], 401);
        }

        $passwordValid = false;
        try {
            $passwordValid = Hash::check($request->password, $user->password);
        } catch (\Throwable $e) {
            if (hash_equals((string)$user->password, (string)$request->password)) {
                $user->password = Hash::make($request->password);
                $user->save();
                $passwordValid = true;
            } else {
                $passwordValid = false;
            }
        }

        if (!$passwordValid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mobile number/email or password.'
            ], 401);
        }

        if ($user->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Please contact support.'
            ], 403);
        }

        // Sanctum Token creation with role abilities
        $token = $user->createToken('tripbd_auth_token', [$user->role])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'phone_verified' => !is_null($user->phone_verified_at),
                'profile' => $user->role === 'customer' ? $user->customerProfile : ($user->role === 'driver' ? $user->driverProfile : null)
            ]
        ], 200);
    }

    /**
     * Logout and revoke current token.
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ], 200);
    }

    /**
     * Password Reset Step 1: Send OTP.
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No TripBD account found with this mobile number.'
            ], 404);
        }

        $result = $this->otpService->generateOtp($request->phone, 'password_reset');
        return response()->json($result, $result['success'] ? 200 : 429);
    }

    /**
     * Password Reset Step 2: Verify Reset OTP.
     * POST /api/v1/auth/verify-reset-otp
     */
    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'otp_code' => ['required', 'string', 'size:6']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->otpService->verifyOtp($request->phone, $request->otp_code, 'password_reset');
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Password Reset Step 3: Set New Password.
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'otp_code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Verify OTP was valid
        $otpCheck = $this->otpService->verifyOtp($request->phone, $request->otp_code, 'password_reset');
        if (!$otpCheck['success']) {
            return response()->json($otpCheck, 400);
        }

        // Update password and revoke old tokens for security
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login with your new password.'
        ], 200);
    }

    /**
     * Current authenticated user profile.
     * GET /api/v1/me
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'avatar' => $user->avatar,
                'phone_verified' => !is_null($user->phone_verified_at),
                'customer_profile' => $user->role === 'customer' ? $user->customerProfile : null,
                'driver_profile' => $user->role === 'driver' ? $user->driverProfile : null,
                'wallet' => $user->role === 'driver' ? $user->wallet : null,
            ]
        ]);
    }

    /**
     * Update basic user profile (name, email).
     * PUT /api/v1/me
     */
    public function updateMe(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email,' . $user->id],
            'avatar' => ['nullable', 'string', 'max:255']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent modifying role, status, phone, or balance through PUT /me
        $user->update($request->only(['name', 'email', 'avatar']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->only(['id', 'name', 'phone', 'email', 'role', 'status', 'avatar'])
        ]);
    }
}
