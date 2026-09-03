<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\VehicleType;
use App\Models\ServiceCategory;
use App\Models\PromoCode;
use App\Services\DistanceCalculationService;
use App\Services\FareCalculationService;
use Exception;

class TripController extends Controller
{
    protected DistanceCalculationService $distanceService;
    protected FareCalculationService $fareService;

    public function __construct(
        DistanceCalculationService $distanceService,
        FareCalculationService $fareService
    ) {
        $this->distanceService = $distanceService;
        $this->fareService = $fareService;
    }

    /**
     * POST /api/v1/trips/estimate
     * Calculate distance and server-computed fare estimate.
     */
    public function estimate(Request $request): JsonResponse
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
            'passenger_count' => 'nullable|integer|min:1|max:100',
            'load_weight' => 'nullable|numeric|min:0',
            'rental_hours' => 'nullable|integer|min:1',
            'promo_code' => 'nullable|string|max:50',
            'scheduled_at' => 'nullable|date'
        ]);

        try {
            $vehicleType = VehicleType::findOrFail($validated['vehicle_type_id']);
            $serviceCategory = ServiceCategory::findOrFail($validated['service_category_id']);

            // 1. Calculate Geodesic Distance
            $distanceKm = $this->distanceService->calculateDistance(
                (float)$validated['pickup_latitude'],
                (float)$validated['pickup_longitude'],
                (float)$validated['destination_latitude'],
                (float)$validated['destination_longitude']
            );

            // 2. Estimate Duration
            $estimatedDurationMinutes = $this->distanceService->estimateDurationMinutes(
                $distanceKm,
                $serviceCategory->slug
            );

            // 3. Compute Server-Authoritative Fare Breakdown
            $fareBreakdown = $this->fareService->calculateFare([
                'vehicle_type' => $vehicleType,
                'service_category' => $serviceCategory,
                'distance_km' => $distanceKm,
                'duration_minutes' => $estimatedDurationMinutes,
                'trip_type' => $validated['trip_type'] ?? 'one_way',
                'passenger_count' => $validated['passenger_count'] ?? 1,
                'load_weight' => $validated['load_weight'] ?? null,
                'rental_hours' => $validated['rental_hours'] ?? null,
                'promo_code' => $validated['promo_code'] ?? null,
                'user_id' => $request->user()?->id
            ]);

            return response()->json([
                'success' => true,
                'data' => array_merge($fareBreakdown, [
                    'service_category' => [
                        'id' => $serviceCategory->id,
                        'name' => $serviceCategory->name,
                        'slug' => $serviceCategory->slug
                    ],
                    'vehicle_type' => [
                        'id' => $vehicleType->id,
                        'name' => $vehicleType->name,
                        'slug' => $vehicleType->slug,
                        'passenger_capacity' => $vehicleType->passenger_capacity,
                        'load_capacity' => $vehicleType->load_capacity
                    ],
                    'pickup_address' => $validated['pickup_address'],
                    'destination_address' => $validated['destination_address'],
                    'scheduled_at' => $validated['scheduled_at'] ?? null
                ])
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
