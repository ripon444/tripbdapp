<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DriverProfile;
use App\Models\DriverDocument;
use App\Models\Vehicle;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AdminAuthController extends Controller
{
    /**
     * Admin login.
     * POST /api/v1/admin/login
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

        $user = User::where('role', 'admin')
            ->where(function ($q) use ($request) {
                $q->where('email', $request->login)
                  ->orWhere('phone', $request->login);
            })
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid administrative credentials.'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Administrator account is not active.'
            ], 403);
        }

        $token = $user->createToken('tripbd_admin_token', ['admin'])->plainTextToken;

        // Log admin login
        AdminLog::create([
            'admin_id' => $user->id,
            'action' => 'login',
            'module' => 'Authentication',
            'details' => ['ip' => $request->ip(), 'user_agent' => $request->userAgent()],
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * List drivers for admin review.
     * GET /api/v1/admin/drivers
     */
    public function listDrivers(Request $request)
    {
        $status = $request->query('status'); // 'pending', 'approved', 'rejected'

        $query = DriverProfile::with(['user', 'vehicles.vehicleType', 'documents']);

        if ($status) {
            $query->where('verification_status', $status);
        }

        $drivers = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $drivers
        ]);
    }

    /**
     * Verify/Approve/Reject Driver KYC.
     * POST /api/v1/admin/drivers/{id}/verify
     */
    public function verifyDriver(Request $request, $id)
    {
        $driverProfile = DriverProfile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:approve,reject,suspend'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'max:255']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $statusMap = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'suspend' => 'suspended'
        ];

        $newStatus = $statusMap[$request->action];
        $driverProfile->update([
            'verification_status' => $newStatus,
            'rejection_reason' => $request->rejection_reason ?? null
        ]);

        // If approved, also approve primary vehicle if pending
        if ($newStatus === 'approved') {
            Vehicle::where('driver_id', $driverProfile->id)
                ->where('verification_status', 'pending')
                ->update(['verification_status' => 'approved', 'status' => 'active']);
        }

        // Log admin audit trail
        AdminLog::create([
            'admin_id' => $request->user()->id,
            'action' => "driver_{$request->action}",
            'module' => 'DriverKYC',
            'target_id' => $driverProfile->id,
            'details' => ['status' => $newStatus, 'reason' => $request->rejection_reason],
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Driver verification status updated to {$newStatus}.",
            'driver' => $driverProfile->fresh(['user', 'vehicles'])
        ]);
    }
}
