<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Booking;
use App\Models\DriverProfile;
use App\Models\DriverLocation;
use App\Models\Vehicle;
use App\Services\BookingService;
use App\Services\DistanceCalculationService;
use Exception;

class DriverTripController extends Controller
{
    protected BookingService $bookingService;
    protected DistanceCalculationService $distanceService;

    public function __construct(
        BookingService $bookingService,
        DistanceCalculationService $distanceService
    ) {
        $this->bookingService = $bookingService;
        $this->distanceService = $distanceService;
    }

    /**
     * POST /api/v1/driver/online
     * Toggle driver to online status after verifying KYC and active vehicle.
     */
    public function goOnline(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile || $profile->verification_status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Your driver profile is not yet approved by administration.'
            ], 403);
        }

        $hasApprovedVehicle = Vehicle::where('driver_id', $profile->id)
            ->where('verification_status', 'approved')
            ->where('status', 'active')
            ->exists();

        if (!$hasApprovedVehicle) {
            return response()->json([
                'success' => false,
                'message' => 'You must have at least one approved and active vehicle before going online.'
            ], 422);
        }

        $profile->online_status = 'online';
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'You are now online and ready to receive trip requests.',
            'data' => [
                'online_status' => 'online',
                'driver' => $profile
            ]
        ]);
    }

    /**
     * POST /api/v1/driver/offline
     */
    public function goOffline(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return response()->json(['success' => false, 'message' => 'Driver profile not found.'], 404);
        }

        // Prevent going offline during an active trip
        $activeTrip = Booking::where('driver_id', $user->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'arrived', 'loading', 'trip_started'])
            ->first();

        if ($activeTrip) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot go offline while you have an active ongoing trip.'
            ], 422);
        }

        $profile->online_status = 'offline';
        $profile->save();

        return response()->json([
            'success' => true,
            'message' => 'You are now offline.',
            'data' => [
                'online_status' => 'offline',
                'driver' => $profile
            ]
        ]);
    }

    /**
     * GET /api/v1/driver/status
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->driverProfile;
        $activeTrip = Booking::where('driver_id', $user->id)
            ->whereIn('status', ['driver_assigned', 'driver_arriving', 'arrived', 'loading', 'trip_started'])
            ->with(['customer:id,name,phone,avatar', 'serviceCategory', 'vehicleType'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'driver_id' => $user->id,
                'verification_status' => $profile?->verification_status ?? 'pending',
                'online_status' => $profile?->online_status ?? 'offline',
                'rating_avg' => (float)($profile?->rating_avg ?? 5.0),
                'total_trips' => (int)($profile?->total_trips ?? 0),
                'active_trip' => $activeTrip
            ]
        ]);
    }

    /**
     * POST /api/v1/driver/location
     * Record driver GPS telemetry heartbeat.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:20.0,27.0',
            'longitude' => 'required|numeric|between:88.0,93.0',
            'heading' => 'nullable|numeric|between:0,360',
            'speed' => 'nullable|numeric|min:0',
            'accuracy' => 'nullable|numeric|min:0',
            'booking_id' => 'nullable|exists:bookings,id'
        ]);

        $user = $request->user();

        $loc = DriverLocation::create([
            'driver_id' => $user->id,
            'booking_id' => $validated['booking_id'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'heading' => $validated['heading'] ?? 0.0,
            'speed' => $validated['speed'] ?? 0.0,
            'accuracy' => $validated['accuracy'] ?? 10.0,
            'recorded_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location recorded.',
            'data' => $loc
        ]);
    }

    /**
     * GET /api/v1/driver/trip-requests
     * Available trip requests for the driver's vehicle type.
     */
    public function getTripRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile || $profile->verification_status !== 'approved' || $profile->online_status === 'offline') {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Get driver's approved vehicle types
        $vehicleTypeIds = Vehicle::where('driver_id', $profile->id)
            ->where('verification_status', 'approved')
            ->where('status', 'active')
            ->pluck('vehicle_type_id')
            ->toArray();

        $requests = Booking::whereIn('status', ['pending', 'searching_driver'])
            ->whereNull('driver_id')
            ->whereIn('vehicle_type_id', $vehicleTypeIds)
            ->with(['customer:id,name,phone,avatar', 'serviceCategory', 'vehicleType'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * POST /api/v1/driver/bookings/{id}/accept
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $booking = $this->bookingService->acceptBooking($id, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Trip request accepted successfully. Please proceed to pickup.',
                'data' => $booking->load(['customer:id,name,phone,avatar', 'serviceCategory', 'vehicleType'])
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/v1/driver/bookings/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Trip request dismissed.'
        ]);
    }

    /**
     * POST /api/v1/driver/bookings/{id}/arrived
     */
    public function arrived(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $booking = $this->bookingService->markArrived($id, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Arrival recorded. Waiting for customer / cargo loading.',
                'data' => $booking
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/v1/driver/bookings/{id}/start
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        try {
            $booking = $this->bookingService->startTrip($id, $user->id);

            return response()->json([
                'success' => true,
                'message' => 'Trip started. Drive safely.',
                'data' => $booking
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/v1/driver/bookings/{id}/complete
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $customFinalFare = $request->has('final_fare') ? (float)$request->final_fare : null;

        try {
            $booking = $this->bookingService->completeTrip($id, $user->id, $customFinalFare);

            return response()->json([
                'success' => true,
                'message' => 'Trip completed successfully. Collect payment from customer.',
                'data' => $booking
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * POST /api/v1/driver/bookings/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        try {
            $booking = $this->bookingService->cancelBooking(
                $id,
                $user->id,
                'driver',
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Trip cancelled by driver.',
                'data' => $booking
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
