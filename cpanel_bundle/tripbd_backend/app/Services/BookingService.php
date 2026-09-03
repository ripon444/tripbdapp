<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\DriverProfile;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BookingService
{
    /**
     * Valid state transitions map
     */
    const VALID_TRANSITIONS = [
        'pending' => ['searching_driver', 'driver_assigned', 'cancelled_by_customer', 'cancelled_by_admin'],
        'searching_driver' => ['driver_assigned', 'cancelled_by_customer', 'cancelled_by_admin'],
        'driver_assigned' => ['driver_arriving', 'arrived', 'cancelled_by_customer', 'cancelled_by_driver', 'cancelled_by_admin'],
        'driver_arriving' => ['arrived', 'cancelled_by_customer', 'cancelled_by_driver', 'cancelled_by_admin'],
        'arrived' => ['loading', 'trip_started', 'cancelled_by_customer', 'cancelled_by_driver'],
        'loading' => ['trip_started', 'cancelled_by_customer'],
        'trip_started' => ['trip_completed'],
        'trip_completed' => [],
        'cancelled_by_customer' => [],
        'cancelled_by_driver' => [],
        'cancelled_by_admin' => []
    ];

    /**
     * Generate a collision-safe unique booking number.
     * Format: TRP-YYYYMMDD-XXXXXX
     */
    public function generateBookingNumber(): string
    {
        $datePrefix = date('Ymd');
        $randomSeq = str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $number = "TRP-{$datePrefix}-{$randomSeq}";

        // Ensure absolute uniqueness
        while (Booking::where('booking_number', $number)->exists()) {
            $randomSeq = str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $number = "TRP-{$datePrefix}-{$randomSeq}";
        }

        return $number;
    }

    /**
     * Check if a transition between two statuses is allowed.
     */
    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        $allowed = self::VALID_TRANSITIONS[$fromStatus] ?? [];
        return in_array($toStatus, $allowed, true);
    }

    /**
     * Create a new booking with initial history record.
     */
    public function createBooking(array $data, int $customerId): Booking
    {
        return DB::transaction(function () use ($data, $customerId) {
            $data['booking_number'] = $this->generateBookingNumber();
            $data['customer_id'] = $customerId;
            $data['status'] = $data['status'] ?? 'searching_driver';
            $data['payment_status'] = $data['payment_status'] ?? 'pending';

            $booking = Booking::create($data);

            // Record initial history
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => $booking->status,
                'changed_by_user_id' => $customerId,
                'notes' => 'Trip booking created by customer.'
            ]);

            return $booking;
        });
    }

    /**
     * Concurrency-safe driver acceptance of a trip request.
     */
    public function acceptBooking(int $bookingId, int $driverId): Booking
    {
        return DB::transaction(function () use ($bookingId, $driverId) {
            // Lock the booking row for update
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

            // Verify driver is approved and online
            $driverUser = User::with(['driverProfile', 'driverVehicles'])->findOrFail($driverId);
            $profile = $driverUser->driverProfile;

            if (!$profile || $profile->verification_status !== 'approved') {
                throw new RuntimeException("Only approved drivers can accept trips.");
            }

            if ($profile->online_status === 'offline') {
                throw new RuntimeException("You must be online to accept trips.");
            }

            // Concurrency check: Ensure booking is still available
            if ($booking->driver_id !== null || !in_array($booking->status, ['pending', 'searching_driver'])) {
                throw new RuntimeException("This trip has already been accepted by another driver or is no longer available.");
            }

            // Find matching approved vehicle
            $vehicle = $driverUser->driverVehicles
                ->where('verification_status', 'approved')
                ->where('status', 'active')
                ->first();

            $booking->driver_id = $driverId;
            $booking->vehicle_id = $vehicle ? $vehicle->id : null;
            $booking->status = 'driver_assigned';
            $booking->save();

            // Update driver status to busy
            $profile->online_status = 'busy';
            $profile->save();

            // Log history
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'driver_assigned',
                'changed_by_user_id' => $driverId,
                'notes' => "Driver {$driverUser->name} accepted the trip."
            ]);

            return $booking;
        });
    }

    /**
     * Driver marks arrived at pickup location.
     */
    public function markArrived(int $bookingId, int $driverId): Booking
    {
        return DB::transaction(function () use ($bookingId, $driverId) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->driver_id !== $driverId) {
                throw new RuntimeException("Unauthorized. You are not the assigned driver for this trip.");
            }

            if (!$this->canTransition($booking->status, 'arrived')) {
                throw new InvalidArgumentException("Cannot transition from {$booking->status} to arrived.");
            }

            $booking->status = 'arrived';
            $booking->save();

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'arrived',
                'changed_by_user_id' => $driverId,
                'notes' => 'Driver has arrived at the pickup location.'
            ]);

            return $booking;
        });
    }

    /**
     * Driver starts the trip.
     */
    public function startTrip(int $bookingId, int $driverId): Booking
    {
        return DB::transaction(function () use ($bookingId, $driverId) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->driver_id !== $driverId) {
                throw new RuntimeException("Unauthorized. You are not the assigned driver for this trip.");
            }

            if (!$this->canTransition($booking->status, 'trip_started')) {
                throw new InvalidArgumentException("Cannot transition from {$booking->status} to trip_started.");
            }

            $booking->status = 'trip_started';
            $booking->save();

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'trip_started',
                'changed_by_user_id' => $driverId,
                'notes' => 'Trip started. En route to destination.'
            ]);

            return $booking;
        });
    }

    /**
     * Driver completes the trip and records final fare.
     */
    public function completeTrip(int $bookingId, int $driverId, ?float $customFinalFare = null): Booking
    {
        return DB::transaction(function () use ($bookingId, $driverId, $customFinalFare) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

            if ($booking->driver_id !== $driverId) {
                throw new RuntimeException("Unauthorized. You are not the assigned driver for this trip.");
            }

            if (!$this->canTransition($booking->status, 'trip_completed')) {
                throw new InvalidArgumentException("Cannot transition from {$booking->status} to trip_completed.");
            }

            $booking->status = 'trip_completed';
            $booking->final_fare = $customFinalFare !== null ? $customFinalFare : $booking->estimated_fare;
            $booking->save();

            // Set driver profile back to online and increment total_trips
            $profile = DriverProfile::where('user_id', $driverId)->first();
            if ($profile) {
                $profile->online_status = 'online';
                $profile->total_trips = ($profile->total_trips ?? 0) + 1;
                $profile->save();
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'trip_completed',
                'changed_by_user_id' => $driverId,
                'notes' => "Trip completed successfully. Final Fare: BDT {$booking->final_fare}"
            ]);

            return $booking;
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(int $bookingId, int $userId, string $role, string $reason): Booking
    {
        return DB::transaction(function () use ($bookingId, $userId, $role, $reason) {
            $booking = Booking::where('id', $bookingId)->lockForUpdate()->firstOrFail();

            $targetStatus = "cancelled_by_{$role}";

            if ($role === 'customer' && $booking->customer_id !== $userId) {
                throw new RuntimeException("Unauthorized. You can only cancel your own bookings.");
            }

            if ($role === 'driver' && $booking->driver_id !== $userId) {
                throw new RuntimeException("Unauthorized. You can only cancel trips assigned to you.");
            }

            if (!$this->canTransition($booking->status, $targetStatus)) {
                throw new InvalidArgumentException("Trip in status '{$booking->status}' cannot be cancelled.");
            }

            $booking->status = $targetStatus;
            $booking->cancelled_by = $role;
            $booking->cancellation_reason = $reason;
            $booking->cancelled_at = now();
            $booking->save();

            // If a driver was assigned, revert their online_status to online
            if ($booking->driver_id) {
                $profile = DriverProfile::where('user_id', $booking->driver_id)->first();
                if ($profile && $profile->online_status === 'busy') {
                    $profile->online_status = 'online';
                    $profile->save();
                }
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => $targetStatus,
                'changed_by_user_id' => $userId,
                'notes' => "Trip cancelled by {$role}. Reason: {$reason}"
            ]);

            return $booking;
        });
    }
}
