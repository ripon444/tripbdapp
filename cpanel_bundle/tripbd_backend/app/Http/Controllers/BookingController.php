<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Booking;
use App\Models\VehicleType;
use App\Models\ServiceCategory;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Services\DistanceCalculationService;
use App\Services\FareCalculationService;
use App\Services\BookingService;
use Exception;

class BookingController extends Controller
{
    protected DistanceCalculationService $distanceService;
    protected FareCalculationService $fareService;
    protected BookingService $bookingService;

    public function __construct(
        DistanceCalculationService $distanceService,
        FareCalculationService $fareService,
        BookingService $bookingService
    ) {
        $this->distanceService = $distanceService;
        $this->fareService = $fareService;
        $this->bookingService = $bookingService;
    }

    /**
     * POST /api/v1/bookings
     * Create a new booking with full server-side validation and fare calculation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'pickup_address' => 'required|string|max:500',
            'pickup_latitude' => 'required|numeric|between:20.0,27.0',
            'pickup_longitude' => 'required|numeric|between:88.0,93.0',
            'destination_address' => 'required|string|max:500',
            'destination_latitude' => 'required|numeric|between:20.0,27.0',
            'destination_longitude' => 'required|numeric|between:88.0,93.0',
            'trip_type' => 'nullable|in:one_way,round_trip,return_trip,hourly',
            'scheduled_at' => 'nullable|date',
            'load_description' => 'nullable|string|max:1000',
            'load_weight' => 'nullable|numeric|min:0',
            'passenger_count' => 'nullable|integer|min:1|max:100',
            'luggage_count' => 'nullable|integer|min:0',
            'customer_notes' => 'nullable|string|max:1000',
            'promo_code' => 'nullable|string|max:50',
            'patient_name' => 'nullable|string|max:100',
            'patient_condition' => 'nullable|string|max:255',
            'is_emergency' => 'nullable|boolean',
            'rental_start_date' => 'nullable|date',
            'rental_end_date' => 'nullable|date',
            'rental_duration_hours' => 'nullable|integer|min:1',
            'require_driver' => 'nullable|boolean'
        ]);

        try {
            $user = $request->user();
            $vehicleType = VehicleType::findOrFail($validated['vehicle_type_id']);
            $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);

            // 1. Recalculate Distance Server-side
            $distanceKm = $this->distanceService->calculateDistance(
                (float)$validated['pickup_latitude'],
                (float)$validated['pickup_longitude'],
                (float)$validated['destination_latitude'],
                (float)$validated['destination_longitude']
            );

            $durationMinutes = $this->distanceService->estimateDurationMinutes(
                $distanceKm,
                $serviceCategory->slug
            );

            // 2. Recalculate Fare Server-side (Never trust frontend fare)
            $fareBreakdown = $this->fareService->calculateFare([
                'vehicle_type' => $vehicleType,
                'service_category' => $serviceCategory,
                'distance_km' => $distanceKm,
                'duration_minutes' => $durationMinutes,
                'trip_type' => $validated['trip_type'] ?? 'one_way',
                'passenger_count' => $validated['passenger_count'] ?? 1,
                'load_weight' => $validated['load_weight'] ?? null,
                'rental_hours' => $validated['rental_duration_hours'] ?? null,
                'promo_code' => $validated['promo_code'] ?? null,
                'user_id' => $user->id
            ]);

            // 3. Create Booking Record
            $bookingData = [
                'service_category_id' => $serviceCategory->id,
                'vehicle_type_id' => $vehicleType->id,
                'pickup_address' => $validated['pickup_address'],
                'pickup_latitude' => $validated['pickup_latitude'],
                'pickup_longitude' => $validated['pickup_longitude'],
                'destination_address' => $validated['destination_address'],
                'destination_latitude' => $validated['destination_latitude'],
                'destination_longitude' => $validated['destination_longitude'],
                'distance_km' => $distanceKm,
                'estimated_duration_minutes' => $durationMinutes,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'load_description' => $validated['load_description'] ?? null,
                'load_weight' => $validated['load_weight'] ?? null,
                'passenger_count' => $validated['passenger_count'] ?? 1,
                'luggage_count' => $validated['luggage_count'] ?? 0,
                'trip_type' => $validated['trip_type'] ?? 'one_way',
                'estimated_fare' => $fareBreakdown['total_fare'],
                'status' => 'searching_driver',
                'payment_status' => 'pending',
                'customer_notes' => $validated['customer_notes'] ?? null
            ];

            $booking = $this->bookingService->createBooking($bookingData, $user->id);

            // Record promo code usage if applied
            if (!empty($validated['promo_code']) && $fareBreakdown['discount'] > 0) {
                $promo = PromoCode::where('code', strtoupper(trim($validated['promo_code'])))->first();
                if ($promo) {
                    PromoCodeUsage::create([
                        'promo_code_id' => $promo->id,
                        'user_id' => $user->id,
                        'booking_id' => $booking->id,
                        'discount_amount' => $fareBreakdown['discount']
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully. Searching for nearby drivers.',
                'data' => [
                    'booking' => $booking->load(['serviceCategory', 'vehicleType', 'statusHistory']),
                    'fare_breakdown' => $fareBreakdown
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * GET /api/v1/bookings/{id}
     * Retrieve single booking with security authorization checks.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $booking = Booking::with([
            'customer:id,name,phone,avatar',
            'driver:id,name,phone,avatar',
            'vehicle',
            'serviceCategory',
            'vehicleType',
            'statusHistory' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'driverLocations' => function ($q) {
                $q->orderBy('recorded_at', 'desc')->limit(1);
            }
        ])->findOrFail($id);

        // Security authorization check:
        // Customers can only view their own bookings
        // Drivers can only view bookings assigned to them or unassigned in searching_driver
        // Admins can view any booking
        if ($user->role === 'customer' && $booking->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to view this booking.'
            ], 403);
        }

        if ($user->role === 'driver' && $booking->driver_id !== null && $booking->driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You are not the assigned driver for this booking.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * POST /api/v1/bookings/{id}/cancel
     * Cancel a booking by authenticated customer or admin.
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
                $user->role,
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully.',
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
