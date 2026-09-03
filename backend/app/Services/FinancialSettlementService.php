<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\FinancialSettlement;
use App\Models\SystemSetting;
use App\Models\User;
use InvalidArgumentException;
use RuntimeException;

class FinancialSettlementService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Retrieve current platform commission percentage from system settings.
     */
    public function getCommissionPercentage(): float
    {
        $setting = SystemSetting::where('key', 'platform_commission_percent')->first();
        if ($setting && is_numeric($setting->value)) {
            $val = floatval($setting->value);
            return max(0.0, min(100.0, $val));
        }
        return 15.00; // Default 15%
    }

    /**
     * Calculate financial split for a given fare and commission rate.
     */
    public function calculateSplit(float $grossFare, ?float $customCommissionPercent = null): array
    {
        if ($grossFare < 0) {
            throw new InvalidArgumentException("Gross fare cannot be negative.");
        }

        $commissionPercent = $customCommissionPercent !== null
            ? max(0.0, min(100.0, $customCommissionPercent))
            : $this->getCommissionPercentage();

        $platformCommission = round(($grossFare * $commissionPercent) / 100, 2);
        $driverEarning = round($grossFare - $platformCommission, 2);

        return [
            'gross_fare' => round($grossFare, 2),
            'commission_percent' => round($commissionPercent, 2),
            'platform_commission' => $platformCommission,
            'driver_earning' => $driverEarning,
        ];
    }

    /**
     * Settle a completed booking financially.
     *
     * @param Booking $booking
     * @param string $paymentMethod ('cash', 'bkash', 'nagad', 'rocket', 'card')
     * @return FinancialSettlement
     */
    public function settleBooking(Booking $booking, string $paymentMethod = 'cash'): FinancialSettlement
    {
        if (!$booking->driver_id) {
            throw new RuntimeException("Cannot settle booking #{$booking->id} without an assigned driver.");
        }

        // Prevent double settlement
        $existing = FinancialSettlement::where('booking_id', $booking->id)->first();
        if ($existing && $existing->status === 'settled') {
            throw new RuntimeException("Booking #{$booking->id} has already been financially settled under reference {$existing->settlement_reference}.");
        }

        $grossFare = (float) ($booking->final_fare ?? $booking->estimated_fare ?? 0);
        $split = $this->calculateSplit($grossFare);

        $settlementRef = 'SETTLE-' . date('Ymd') . '-' . sprintf('%06d', $booking->id);

        $settlement = FinancialSettlement::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'driver_id' => $booking->driver_id,
                'settlement_reference' => $settlementRef,
                'gross_fare' => $split['gross_fare'],
                'platform_commission_percent' => $split['commission_percent'],
                'platform_commission_amount' => $split['platform_commission'],
                'driver_earning_amount' => $split['driver_earning'],
                'service_charge_amount' => 0.00,
                'tax_amount' => 0.00,
                'discount_amount' => (float) ($booking->discount ?? 0),
                'status' => 'settled',
                'settled_at' => now(),
            ]
        );

        // Update driver wallet balance
        // For digital payments: Driver receives 85% driver earnings credited to wallet
        // For cash payments: Driver collected 100% cash in hand from customer, so driver owes 15% commission to platform (or net credit depending on platform policy)
        $this->walletService->credit(
            $booking->driver_id,
            $split['driver_earning'],
            'earning',
            'Booking',
            $booking->id,
            "Trip earnings for Booking #{$booking->booking_number} (Gross: ৳{$split['gross_fare']}, Comm {$split['commission_percent']}%: ৳{$split['platform_commission']})"
        );

        return $settlement;
    }
}
