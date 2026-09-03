<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Customer Profile.
     * GET /api/v1/customer/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $profile = $user->customerProfile;

        if (!$profile) {
            $profile = CustomerProfile::firstOrCreate(['user_id' => $user->id]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user->only(['id', 'name', 'phone', 'email', 'avatar', 'status']),
                'customer_profile' => $profile
            ]
        ]);
    }

    /**
     * Update Customer Profile.
     * PUT /api/v1/customer/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $profile = $user->customerProfile;

        if (!$profile) {
            $profile = CustomerProfile::create(['user_id' => $user->id]);
        }

        $validator = Validator::make($request->all(), [
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'district' => ['sometimes', 'string', 'max:100'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile->update($request->only(['address', 'city', 'district', 'emergency_contact', 'date_of_birth']));

        return response()->json([
            'success' => true,
            'message' => 'Customer profile updated successfully',
            'data' => $profile
        ]);
    }
}
