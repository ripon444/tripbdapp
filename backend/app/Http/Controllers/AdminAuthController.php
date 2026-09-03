<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DriverProfile;
use App\Models\DriverDocument;
use App\Models\Vehicle;
use App\Models\AdminLog;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Payment;
use App\Models\Withdrawal;
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

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid administrative credentials.'
            ], 401);
        }

        // Validate password using secure Bcrypt hashing with auto-upgrade if legacy plain text is stored
        $passwordValid = false;
        try {
            $passwordValid = Hash::check($request->password, $user->password);
        } catch (\Throwable $e) {
            // If stored password in DB is legacy unhashed text from manual phpMyAdmin entry during deployment,
            // verify with constant-time equality and immediately upgrade to a true Bcrypt hash
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

    /**
     * List all registered vehicles.
     * GET /api/v1/admin/vehicles
     */
    public function listVehicles(Request $request)
    {
        $query = Vehicle::with(['driver.user', 'vehicleType.serviceCategory']);

        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        $vehicles = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    /**
     * Verify/Approve/Reject Vehicle.
     * POST /api/v1/admin/vehicles/{id}/verify
     */
    public function verifyVehicle(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

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
        $vehicle->update([
            'verification_status' => $newStatus,
            'status' => ($newStatus === 'approved') ? 'active' : 'inactive',
            'rejection_reason' => $request->rejection_reason ?? null
        ]);

        AdminLog::create([
            'admin_id' => $request->user()->id,
            'action' => "vehicle_{$request->action}",
            'module' => 'VehicleVerification',
            'target_id' => $vehicle->id,
            'details' => ['status' => $newStatus, 'reason' => $request->rejection_reason],
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Vehicle status updated to {$newStatus}.",
            'vehicle' => $vehicle->fresh(['driver.user', 'vehicleType'])
        ]);
    }

    /**
     * List all bookings with rich filters.
     * GET /api/v1/admin/bookings
     */
    public function listBookings(Request $request)
    {
        $query = Booking::with(['customer:id,name,phone', 'driver:id,name,phone', 'vehicleType', 'serviceCategory', 'vehicle']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('destination_address', 'like', "%{$search}%");
            });
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * View single booking with full relations and history.
     * GET /api/v1/admin/bookings/{id}
     */
    public function showBooking($id)
    {
        $booking = Booking::with([
            'customer',
            'driver',
            'vehicle',
            'serviceCategory',
            'vehicleType',
            'statusHistory',
            'settlement',
            'payments'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * Admin override / cancel booking status.
     * POST /api/v1/admin/bookings/{id}/status
     */
    public function updateBookingStatus(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:cancelled,trip_completed,driver_assigned,searching_driver',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldStatus = $booking->status;
        $booking->status = $request->status;
        if ($request->status === 'cancelled') {
            $booking->cancellation_reason = $request->notes ?? 'Administrative cancellation';
            $booking->cancelled_by_user_id = $request->user()->id;
        }
        $booking->save();

        BookingStatusHistory::create([
            'booking_id' => $booking->id,
            'status' => $booking->status,
            'changed_by_user_id' => $request->user()->id,
            'notes' => "Admin status override from {$oldStatus} to {$booking->status}. " . ($request->notes ?? '')
        ]);

        AdminLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'update_booking_status',
            'module' => 'BookingManagement',
            'target_id' => $booking->id,
            'details' => ['old_status' => $oldStatus, 'new_status' => $booking->status],
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'success' => true,
            'message' => "Booking status successfully updated to {$booking->status}.",
            'data' => $booking
        ]);
    }

    /**
     * High-level admin dashboard overview metrics.
     * GET /api/v1/admin/overview
     */
    public function dashboardOverview()
    {
        $totalCustomers = User::where('role', 'customer')->count();
        $totalDrivers = DriverProfile::count();
        $pendingKyc = DriverProfile::where('verification_status', 'pending')->count();
        $approvedDrivers = DriverProfile::where('verification_status', 'approved')->count();
        $totalVehicles = Vehicle::count();
        $pendingVehicles = Vehicle::where('verification_status', 'pending')->count();
        $totalBookings = Booking::count();
        $activeBookings = Booking::whereIn('status', ['searching_driver', 'driver_assigned', 'driver_arrived', 'in_transit'])->count();
        $completedBookings = Booking::where('status', 'trip_completed')->count();
        $totalRevenue = (float) Payment::where('status', 'paid')->sum('amount');
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => $totalCustomers,
                'total_drivers' => $totalDrivers,
                'approved_drivers' => $approvedDrivers,
                'pending_kyc' => $pendingKyc,
                'total_vehicles' => $totalVehicles,
                'pending_vehicles' => $pendingVehicles,
                'total_bookings' => $totalBookings,
                'active_bookings' => $activeBookings,
                'completed_bookings' => $completedBookings,
                'total_revenue' => round($totalRevenue, 2),
                'pending_withdrawals' => $pendingWithdrawals,
                'currency' => 'BDT'
            ]
        ]);
    }
}
