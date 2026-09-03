<?php

namespace App\Services;

use App\Models\VehicleType;
use App\Models\ServiceCategory;
use App\Models\PromoCode;
use App\Models\SystemSetting;
use InvalidArgumentException;

class FareCalculationService
{
    /**
     * Calculate comprehensive fare breakdown for a trip request.
     *
     * @param array $params [
     *   'vehicle_type' => VehicleType|array,
     *   'service_category' => ServiceCategory|array,
     *   'distance_km' => float,
     *   'duration_minutes' => int,
     *   'trip_type' => string ('one_way'|'round_trip'|'return_trip'|'hourly'),
     *   'passenger_count' => int,
     *   'load_weight' => float|null (tons or kg),
     *   'rental_hours' => int|null,
     *   'promo_code' => PromoCode|string|null,
     *   'user_id' => int|null,
     * ]
     * @return array Complete breakdown
     */
    public function calculateFare(array $params): array
    {
        $vehicleType = $params['vehicle_type'];
        $serviceCategory = $params['service_category'] ?? null;
        $distanceKm = (float) ($params['distance_km'] ?? 0.0);
        $durationMinutes = (int) ($params['duration_minutes'] ?? 0);
        $tripType = $params['trip_type'] ?? 'one_way';
        $passengerCount = (int) ($params['passenger_count'] ?? 1);
        $loadWeight = isset($params['load_weight']) ? (float) $params['load_weight'] : null;
        $rentalHours = isset($params['rental_hours']) ? (int) $params['rental_hours'] : null;
        $promoCode = $params['promo_code'] ?? null;

        // Vehicle rate variables (support Model or associative array)
        $baseFare = (float) ($vehicleType['base_fare'] ?? $vehicleType->base_fare ?? 0.0);
        $perKmRate = (float) ($vehicleType['per_km_rate'] ?? $vehicleType->per_km_rate ?? 0.0);
        $perHourRate = (float) ($vehicleType['per_hour_rate'] ?? $vehicleType->per_hour_rate ?? 0.0);
        $minimumFare = (float) ($vehicleType['minimum_fare'] ?? $vehicleType->minimum_fare ?? 0.0);
        $maxLoadCapacity = (float) ($vehicleType['load_capacity'] ?? $vehicleType->load_capacity ?? 0.0);
        $maxPassengerCapacity = (int) ($vehicleType['passenger_capacity'] ?? $vehicleType->passenger_capacity ?? 1);

        // 1. Capacity Validations
        if ($passengerCount > $maxPassengerCapacity && $maxPassengerCapacity > 0) {
            throw new InvalidArgumentException("Passenger count ({$passengerCount}) exceeds maximum capacity ({$maxPassengerCapacity}) for this vehicle type.");
        }

        if ($loadWeight !== null && $maxLoadCapacity > 0 && $loadWeight > $maxLoadCapacity) {
            throw new InvalidArgumentException("Load weight ({$loadWeight} Ton) exceeds vehicle maximum capacity ({$maxLoadCapacity} Ton).");
        }

        // 2. Distance Fare Calculation based on Trip Type
        $distanceFare = 0.0;
        $timeFare = 0.0;
        $loadCharge = 0.0;
        $returnTripCharge = 0.0;

        if ($tripType === 'hourly' || ($rentalHours !== null && $rentalHours > 0)) {
            $hours = max(1, $rentalHours ?? ceil($durationMinutes / 60));
            $effectivePerHour = $perHourRate > 0 ? $perHourRate : ($baseFare * 0.5);
            $timeFare = round($hours * $effectivePerHour, 2);
            $distanceFare = round($distanceKm * ($perKmRate * 0.5), 2); // Reduced per-km for rentals
        } else {
            $effectiveDistance = $distanceKm;
            if ($tripType === 'round_trip') {
                $effectiveDistance = $distanceKm * 1.8; // 10% round-trip discount on return leg
            } elseif ($tripType === 'return_trip') {
                // Special discounted rate for empty return vehicles
                $effectiveDistance = $distanceKm * 0.75; // 25% discount for return trip match
                $returnTripCharge = -round($distanceKm * $perKmRate * 0.25, 2); // Marked as negative benefit/deduction
            }
            $distanceFare = round($effectiveDistance * $perKmRate, 2);
        }

        // 3. Load Handling Charge for Cargo / Heavy Trucks
        if ($loadWeight !== null && $loadWeight > 0) {
            // Nominal weight surcharge of 100 BDT per Ton
            $loadCharge = round($loadWeight * 100.0, 2);
        }

        // 4. Subtotal & Minimum Fare
        $subtotal = $baseFare + $distanceFare + $timeFare + $loadCharge;
        if ($subtotal < $minimumFare) {
            $subtotal = $minimumFare;
        }

        // 5. System Service Charge (e.g. 5% platform charge or 30 BDT min)
        $serviceChargePercent = 0.05;
        $serviceCharge = round(max(30.0, $subtotal * $serviceChargePercent), 2);

        // 6. Promo Code Discount
        $discount = 0.0;
        if ($promoCode) {
            $discount = $this->calculatePromoDiscount($promoCode, $subtotal);
        }

        // 7. VAT / Tax (Government Transport Tax, standard 0% to 5% configurable)
        $taxPercent = 0.00; // Tax exempted / inclusive by default in transport
        $tax = round(($subtotal + $serviceCharge - $discount) * $taxPercent, 2);

        // 8. Total Fare
        $totalFare = round(max(0, $subtotal + $serviceCharge - $discount + $tax), 2);

        return [
            'base_fare' => round($baseFare, 2),
            'distance_fare' => round($distanceFare, 2),
            'time_fare' => round($timeFare, 2),
            'load_charge' => round($loadCharge, 2),
            'return_trip_charge' => round($returnTripCharge, 2),
            'service_charge' => round($serviceCharge, 2),
            'discount' => round($discount, 2),
            'tax' => round($tax, 2),
            'total_fare' => round($totalFare, 2),
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'trip_type' => $tripType,
        ];
    }

    /**
     * Calculate promo code discount value.
     */
    protected function calculatePromoDiscount($promoCode, float $subtotal): float
    {
        if (is_string($promoCode)) {
            $promoCode = PromoCode::where('code', strtoupper(trim($promoCode)))
                ->where('status', 'active')
                ->first();
        }

        if (!$promoCode) {
            return 0.0;
        }

        $minFare = (float) ($promoCode->minimum_fare ?? 0.0);
        if ($subtotal < $minFare) {
            return 0.0;
        }

        $type = $promoCode->type ?? 'percentage';
        $value = (float) ($promoCode->value ?? 0.0);
        $maxDiscount = (float) ($promoCode->maximum_discount ?? 999999.0);

        if ($type === 'percentage') {
            $calculated = ($subtotal * $value) / 100.0;
        } else {
            $calculated = $value;
        }

        return round(min($calculated, $maxDiscount, $subtotal), 2);
    }
}
