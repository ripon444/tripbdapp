<?php

namespace App\Services;

use App\Models\User;
use App\Models\DriverProfile;
use App\Models\Vehicle;
use App\Models\DriverLocation;
use App\Models\Booking;

class DriverMatchingService
{
    protected DistanceCalculationService $distanceService;

    public function __construct(DistanceCalculationService $distanceService)
    {
        $this->distanceService = $distanceService;
    }

    /**
     * Find eligible available drivers nearest to the pickup coordinates.
     *
     * @param float $pickupLat
     * @param float $pickupLng
     * @param int $vehicleTypeId
     * @param float $maxRadiusKm
     * @param int $limit
     * @return array List of matched drivers with distance & ETA
     */
    public function findNearestDrivers(
        float $pickupLat,
        float $pickupLng,
        int $vehicleTypeId,
        float $maxRadiusKm = 50.0,
        int $limit = 10
    ): array {
        // 1. Fetch all online, verified, active drivers
        $eligibleDrivers = User::where('role', 'driver')
            ->where('status', 'active')
            ->whereHas('driverProfile', function ($q) {
                $q->where('verification_status', 'approved')
                  ->where('online_status', 'online');
            })
            ->whereHas('driverVehicles', function ($q) use ($vehicleTypeId) {
                $q->where('vehicle_type_id', $vehicleTypeId)
                  ->where('verification_status', 'approved')
                  ->where('status', 'active');
            })
            ->with(['driverProfile', 'driverVehicles' => function ($q) use ($vehicleTypeId) {
                $q->where('vehicle_type_id', $vehicleTypeId)
                  ->where('verification_status', 'approved')
                  ->where('status', 'active');
            }])
            ->get();

        $matched = [];

        foreach ($eligibleDrivers as $driver) {
            // Check if driver has an active non-completed trip
            $hasActiveTrip = Booking::where('driver_id', $driver->id)
                ->whereIn('status', [
                    'driver_assigned',
                    'driver_arriving',
                    'arrived',
                    'loading',
                    'trip_started'
                ])
                ->exists();

            if ($hasActiveTrip) {
                continue;
            }

            // Get driver's latest recorded location
            $latestLoc = DriverLocation::where('driver_id', $driver->id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            $driverLat = $latestLoc ? (float)$latestLoc->latitude : 23.8103; // Default Dhaka centroid if no GPS
            $driverLng = $latestLoc ? (float)$latestLoc->longitude : 90.4125;

            $distanceToPickup = $this->distanceService->calculateDistance(
                $pickupLat,
                $pickupLng,
                $driverLat,
                $driverLng
            );

            if ($distanceToPickup <= $maxRadiusKm) {
                $activeVehicle = $driver->driverVehicles->first();
                $matched[] = [
                    'driver_id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'rating_avg' => (float)($driver->driverProfile->rating_avg ?? 5.0),
                    'total_trips' => (int)($driver->driverProfile->total_trips ?? 0),
                    'distance_km' => $distanceToPickup,
                    'estimated_arrival_minutes' => (int)max(3, ceil($distanceToPickup * 2)),
                    'latitude' => $driverLat,
                    'longitude' => $driverLng,
                    'vehicle' => $activeVehicle ? [
                        'id' => $activeVehicle->id,
                        'registration_number' => $activeVehicle->registration_number,
                        'brand' => $activeVehicle->brand,
                        'model' => $activeVehicle->model,
                        'color' => $activeVehicle->color
                    ] : null
                ];
            }
        }

        // Sort by nearest distance first
        usort($matched, function ($a, $b) {
            return $a['distance_km'] <=> $b['distance_km'];
        });

        return array_slice($matched, 0, $limit);
    }
}
