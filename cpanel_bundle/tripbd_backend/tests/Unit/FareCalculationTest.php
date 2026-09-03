<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\FareCalculationService;
use InvalidArgumentException;

class FareCalculationTest extends TestCase
{
    protected FareCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FareCalculationService();
    }

    /** @test */
    public function calculates_standard_oneway_truck_fare(): void
    {
        $vehicleType = [
            'base_fare' => 1500.00,
            'per_km_rate' => 35.00,
            'per_hour_rate' => 0.00,
            'minimum_fare' => 2000.00,
            'load_capacity' => 5.0, // 5 Ton
            'passenger_capacity' => 2
        ];

        $breakdown = $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 100.0,
            'duration_minutes' => 180,
            'trip_type' => 'one_way',
            'passenger_count' => 1,
            'load_weight' => 2.5 // 2.5 Ton
        ]);

        $this->assertEquals(1500.00, $breakdown['base_fare']);
        $this->assertEquals(3500.00, $breakdown['distance_fare']);
        $this->assertEquals(250.00, $breakdown['load_charge']); // 2.5 * 100
        $this->assertGreaterThan(0, $breakdown['service_charge']);
        $this->assertGreaterThan(5000, $breakdown['total_fare']);
    }

    /** @test */
    public function enforces_truck_weight_capacity_validation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $vehicleType = [
            'base_fare' => 1000.00,
            'per_km_rate' => 30.00,
            'load_capacity' => 3.0, // 3 Ton max
            'passenger_capacity' => 2
        ];

        // Attempting to book 5 Ton load on a 3 Ton vehicle
        $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 50.0,
            'duration_minutes' => 90,
            'passenger_count' => 1,
            'load_weight' => 5.0
        ]);
    }

    /** @test */
    public function enforces_passenger_capacity_validation(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $vehicleType = [
            'base_fare' => 50.00,
            'per_km_rate' => 15.00,
            'passenger_capacity' => 1 // Bike ride max 1 passenger
        ];

        // Attempting 3 passengers on a bike
        $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 10.0,
            'duration_minutes' => 25,
            'passenger_count' => 3
        ]);
    }

    /** @test */
    public function return_truck_trip_type_receives_discounted_rate(): void
    {
        $vehicleType = [
            'base_fare' => 1000.00,
            'per_km_rate' => 30.00,
            'minimum_fare' => 1000.00,
            'load_capacity' => 5.0,
            'passenger_capacity' => 2
        ];

        $oneWayFare = $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 100.0,
            'duration_minutes' => 180,
            'trip_type' => 'one_way'
        ]);

        $returnTripFare = $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 100.0,
            'duration_minutes' => 180,
            'trip_type' => 'return_trip'
        ]);

        // Return trip should be significantly cheaper
        $this->assertLessThan($oneWayFare['total_fare'], $returnTripFare['total_fare']);
    }

    /** @test */
    public function hourly_car_rental_calculates_time_based_fare(): void
    {
        $vehicleType = [
            'base_fare' => 500.00,
            'per_km_rate' => 20.00,
            'per_hour_rate' => 400.00,
            'minimum_fare' => 1500.00,
            'load_capacity' => 0.5,
            'passenger_capacity' => 4
        ];

        $breakdown = $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 40.0,
            'duration_minutes' => 360,
            'trip_type' => 'hourly',
            'rental_hours' => 6
        ]);

        $this->assertEquals(2400.00, $breakdown['time_fare']); // 6 hours * 400
        $this->assertGreaterThan(2500, $breakdown['total_fare']);
    }

    /** @test */
    public function promo_codes_apply_valid_discount_and_enforce_caps(): void
    {
        $vehicleType = [
            'base_fare' => 500.00,
            'per_km_rate' => 25.00,
            'minimum_fare' => 500.00,
            'load_capacity' => 1.0,
            'passenger_capacity' => 4
        ];

        // 50% discount capped at BDT 300
        $promo = (object) [
            'code' => 'TRIPBD50',
            'type' => 'percentage',
            'value' => 50.0,
            'minimum_fare' => 200.0,
            'maximum_discount' => 300.0,
            'status' => 'active'
        ];

        $breakdown = $this->service->calculateFare([
            'vehicle_type' => $vehicleType,
            'distance_km' => 40.0, // Subtotal: 500 + 1000 = 1500
            'duration_minutes' => 60,
            'promo_code' => $promo
        ]);

        $this->assertEquals(300.00, $breakdown['discount']);
        $this->assertGreaterThan(1200.00, $breakdown['total_fare']);
    }

    /** @test */
    public function commission_distribution_calculates_correct_driver_and_platform_splits(): void
    {
        $fare = 10000.00;
        $driverEarningRate = 0.85;
        $platformCommissionRate = 0.15;

        $driverEarnings = round($fare * $driverEarningRate, 2);
        $platformCommission = round($fare * $platformCommissionRate, 2);

        $this->assertEquals(8500.00, $driverEarnings);
        $this->assertEquals(1500.00, $platformCommission);
        $this->assertEquals($fare, $driverEarnings + $platformCommission);
    }
}
