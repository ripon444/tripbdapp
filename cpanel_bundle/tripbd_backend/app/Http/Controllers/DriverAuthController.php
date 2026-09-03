<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DriverProfile;
use App\Models\DriverDocument;
use App\Models\Vehicle;
use App\Models\VehicleDocument;
use App\Models\Wallet;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DriverAuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Driver registration.
     * POST /api/v1/driver/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'unique:users,phone', 'regex:/^(?:\+8801|01)[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'unique:users,email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nid_number' => ['required', 'string', 'max:30'],
            'driving_license_number' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:100'],
            'otp_code' => ['nullable', 'string', 'size:6']
        ], [
            'phone.unique' => 'This mobile number is already registered.',
            'phone.regex' => 'Please provide a valid 11-digit Bangladeshi mobile number.',
            'nid_number.required' => 'National ID (NID) number is required for driver registration.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify OTP if provided
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
                'role' => 'driver',
                'status' => 'active',
                'phone_verified_at' => $request->filled('otp_code') ? Carbon::now() : null,
            ]);

            // Create Driver Profile with default 'pending' verification & 'offline' status
            $driverProfile = DriverProfile::create([
                'user_id' => $user->id,
                'nid_number' => $request->nid_number,
                'driving_license_number' => $request->driving_license_number,
                'address' => $request->address,
                'district' => $request->district,
                'verification_status' => 'pending',
                'online_status' => 'offline',
                'rating_avg' => 5.00,
                'total_trips' => 0
            ]);

            // Initialize Driver Wallet with zero balance
            Wallet::create([
                'driver_id' => $user->id,
                'balance' => 0.00,
                'currency' => 'BDT'
            ]);

            DB::commit();

            $token = $user->createToken('tripbd_driver_token', ['driver'])->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Driver account registered successfully. Verification status: Pending admin review.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'status' => $user->status,
                    'driver_profile' => [
                        'id' => $driverProfile->id,
                        'nid_number' => $driverProfile->nid_number,
                        'driving_license_number' => $driverProfile->driving_license_number,
                        'district' => $driverProfile->district,
                        'verification_status' => $driverProfile->verification_status,
                        'online_status' => $driverProfile->online_status,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Driver registration failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get Driver Profile with KYC Documents and Vehicles.
     * GET /api/v1/driver/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $driverProfile = $user->driverProfile;

        if (!$driverProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found.'
            ], 404);
        }

        $driverProfile->load(['vehicles.vehicleType', 'documents', 'activeVehicle']);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'phone', 'email', 'avatar', 'status']),
                'driver_profile' => $driverProfile,
                'wallet' => $user->wallet
            ]
        ]);
    }

    /**
     * Update Driver Profile (Address, Emergency Contact).
     * PUT /api/v1/driver/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'address' => ['sometimes', 'string', 'max:255'],
            'district' => ['sometimes', 'string', 'max:100'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Driver cannot update verification_status, rating_avg, or total_trips
        $profile->update($request->only(['address', 'district', 'emergency_contact', 'experience_years']));

        return response()->json([
            'success' => true,
            'message' => 'Driver profile updated successfully',
            'data' => $profile
        ]);
    }

    /**
     * Upload Driver KYC Document (NID, Driving License).
     * POST /api/v1/driver/documents
     */
    public function uploadDocument(Request $request)
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => ['required', 'in:nid_front,nid_back,driving_license,profile_photo'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // Max 5MB
        ], [
            'file.mimes' => 'Document must be a valid JPG, JPEG, PNG, or PDF file.',
            'file.max' => 'Document size cannot exceed 5MB.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        // Secure private storage outside public_html
        $path = $file->store("private/kyc/driver_{$profile->id}", 'local');

        $doc = DriverDocument::updateOrCreate(
            [
                'driver_id' => $profile->id,
                'document_type' => $request->document_type
            ],
            [
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'verification_status' => 'pending',
                'rejection_reason' => null
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'KYC Document uploaded securely and marked for verification.',
            'document' => [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'verification_status' => $doc->verification_status,
                'uploaded_at' => $doc->updated_at
            ]
        ], 201);
    }

    /**
     * Register a Driver Vehicle.
     * POST /api/v1/driver/vehicles
     */
    public function registerVehicle(Request $request)
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'vehicle_type_id' => ['required', 'exists:vehicle_types,id'],
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'year' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'color' => ['required', 'string', 'max:30'],
            'engine_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:50']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $vehicle = Vehicle::create([
            'driver_id' => $profile->id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'registration_number' => $request->registration_number,
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'color' => $request->color,
            'engine_number' => $request->engine_number,
            'chassis_number' => $request->chassis_number,
            'verification_status' => 'pending',
            'status' => 'inactive'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle registered successfully and submitted for admin review.',
            'vehicle' => $vehicle->load('vehicleType')
        ], 201);
    }
}
