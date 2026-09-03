<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\DistanceCalculationService;

class DistanceCalculationTest extends TestCase
{
    protected DistanceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DistanceCalculationService();
    }

    /** @test */
    public function haversine_calculates_accurate_dhaka_to_chattogram_distance(): void
    {
        // Dhaka coordinates: 23.8103, 90.4125
        // Chattogram coordinates: 22.3569, 91.7832
        $distance = $this->service->calculateDistance(23.8103, 90.4125, 22.3569, 91.7832);

        // Geodesic distance is approximately 213 - 220 km aerial
        $this->assertGreaterThan(200, $distance);
        $this->assertLessThan(230, $distance);
        $this->assertEquals(214.24, round($distance, 2));
    }

    /** @test */
    public function haversine_handles_short_intra_city_distances(): void
    {
        // Banani to Motijheel, Dhaka (~8 km)
        $distance = $this->service->calculateDistance(23.7937, 90.4066, 23.7330, 90.4172);

        $this->assertGreaterThan(5.0, $distance);
        $this->assertLessThan(10.0, $distance);
    }

    /** @test */
    public function duration_estimation_accounts_for_traffic_and_category(): void
    {
        $durationTruck = $this->service->estimateDurationMinutes(100.0, 'truck-trip');
        $durationAmbulance = $this->service->estimateDurationMinutes(100.0, 'ambulance-trip');

        $this->assertGreaterThan(120, $durationTruck);
        // Ambulance has higher priority speed than heavy cargo truck
        $this->assertLessThan($durationTruck, $durationAmbulance);
    }

    /** @test */
    public function coordinate_validator_enforces_bangladesh_bounds(): void
    {
        $this->assertTrue($this->service->isValidBangladeshCoordinate(23.8103, 90.4125)); // Dhaka
        $this->assertTrue($this->service->isValidBangladeshCoordinate(21.4272, 92.0058)); // Cox's Bazar
        $this->assertFalse($this->service->isValidBangladeshCoordinate(51.5074, -0.1278)); // London (Invalid)
        $this->assertFalse($this->service->isValidBangladeshCoordinate(0.0, 0.0)); // Null Island (Invalid)
    }
}
