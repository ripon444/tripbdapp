<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Services\DistanceCalculationService;
use App\Services\FareCalculationService;
use InvalidArgumentException;

class BookingEngineTest extends TestCase
{
    protected BookingService $bookingService;
    protected DistanceCalculationService $distanceService;
    protected FareCalculationService $fareService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = new BookingService();
        $this->distanceService = new DistanceCalculationService();
        $this->fareService = new FareCalculationService();
    }

    /** @test */
    public function generates_unique_booking_number_with_proper_prefix_and_format(): void
    {
        $numbers = [];
        for ($i = 0; $i < 50; $i++) {
            $num = $this->bookingService->generateBookingNumber();
            $this->assertMatchesRegularExpression('/^TRP-\d{8}-\d{6}$/', $num);
            $this->assertNotContains($num, $numbers);
            $numbers[] = $num;
        }
    }

    /** @test */
    public function state_machine_enforces_valid_transitions(): void
    {
        // Valid transitions
        $this->assertTrue($this->bookingService->canTransition('pending', 'searching_driver'));
        $this->assertTrue($this->bookingService->canTransition('searching_driver', 'driver_assigned'));
        $this->assertTrue($this->bookingService->canTransition('driver_assigned', 'arrived'));
        $this->assertTrue($this->bookingService->canTransition('arrived', 'trip_started'));
        $this->assertTrue($this->bookingService->canTransition('trip_started', 'trip_completed'));

        // Invalid transitions
        $this->assertFalse($this->bookingService->canTransition('trip_completed', 'pending'));
        $this->assertFalse($this->bookingService->canTransition('trip_completed', 'trip_started'));
        $this->assertFalse($this->bookingService->canTransition('cancelled_by_customer', 'trip_started'));
        $this->assertFalse($this->bookingService->canTransition('pending', 'trip_completed'));
    }

    /** @test */
    public function customer_cancellation_rules_validation(): void
    {
        $this->assertTrue($this->bookingService->canTransition('pending', 'cancelled_by_customer'));
        $this->assertTrue($this->bookingService->canTransition('searching_driver', 'cancelled_by_customer'));
        $this->assertTrue($this->bookingService->canTransition('driver_assigned', 'cancelled_by_customer'));
        $this->assertTrue($this->bookingService->canTransition('arrived', 'cancelled_by_customer'));
        // Cannot cancel once trip is underway (trip_started)
        $this->assertFalse($this->bookingService->canTransition('trip_started', 'cancelled_by_customer'));
    }

    /** @test */
    public function driver_cancellation_rules_validation(): void
    {
        $this->assertTrue($this->bookingService->canTransition('driver_assigned', 'cancelled_by_driver'));
        $this->assertTrue($this->bookingService->canTransition('arrived', 'cancelled_by_driver'));
        // Driver cannot cancel before being assigned or once trip is started
        $this->assertFalse($this->bookingService->canTransition('searching_driver', 'cancelled_by_driver'));
        $this->assertFalse($this->bookingService->canTransition('trip_started', 'cancelled_by_driver'));
    }
}
