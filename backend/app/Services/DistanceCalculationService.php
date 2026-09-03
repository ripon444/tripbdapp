<?php

namespace App\Services;

class DistanceCalculationService
{
    const EARTH_RADIUS_KM = 6371.0;

    /**
     * Calculate geodesic distance between two coordinate pairs using Haversine formula.
     *
     * @param float $lat1 Pickup Latitude
     * @param float $lon1 Pickup Longitude
     * @param float $lat2 Destination Latitude
     * @param float $lon2 Destination Longitude
     * @return float Distance in Kilometers rounded to 2 decimal places
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = self::EARTH_RADIUS_KM * $c;

        return round(max(0.5, $distance), 2);
    }

    /**
     * Estimate trip duration in minutes based on distance and vehicle category.
     *
     * @param float $distanceKm
     * @param string $serviceCategorySlug
     * @return int Duration in minutes
     */
    public function estimateDurationMinutes(float $distanceKm, string $serviceCategorySlug = 'truck'): int
    {
        // Realistic average speeds in Bangladesh road conditions (km/h)
        $speedMap = [
            'bike-ride' => 35.0,
            'cng-auto-rickshaw' => 25.0,
            'taxi-trip' => 35.0,
            'private-car' => 40.0,
            'ambulance-trip' => 45.0,
            'truck-trip' => 30.0,
            'return-truck' => 30.0,
            'car-rental' => 35.0,
        ];

        $averageSpeedKmH = $speedMap[$serviceCategorySlug] ?? 30.0;
        $minutes = ($distanceKm / $averageSpeedKmH) * 60;

        // Base traffic buffer of 10 minutes
        $totalMinutes = ceil($minutes + 10);

        return (int) max(15, $totalMinutes);
    }

    /**
     * Validate whether coordinates fall within valid Bangladesh geographic bounds.
     * Bangladesh approx bounding box: Lat [20.5, 26.8], Long [88.0, 92.8]
     */
    public function isValidBangladeshCoordinate(float $lat, float $lon): bool
    {
        return ($lat >= 20.0 && $lat <= 27.0 && $lon >= 88.0 && $lon <= 93.0);
    }
}
