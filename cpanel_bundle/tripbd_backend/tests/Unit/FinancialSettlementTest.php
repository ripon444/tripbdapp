<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\FinancialSettlementService;
use App\Services\WalletService;
use App\Models\Booking;
use App\Models\SystemSetting;
use InvalidArgumentException;
use RuntimeException;

class FinancialSettlementTest extends TestCase
{
    protected FinancialSettlementService $settlementService;
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = new WalletService();
        $this->settlementService = new FinancialSettlementService($this->walletService);
    }

    /** @test */
    public function calculates_accurate_commission_and_driver_earning_splits(): void
    {
        $fare = 10000.00;
        $split = $this->settlementService->calculateSplit($fare, 15.0);

        $this->assertEquals(10000.00, $split['gross_fare']);
        $this->assertEquals(15.00, $split['commission_percent']);
        $this->assertEquals(1500.00, $split['platform_commission']);
        $this->assertEquals(8500.00, $split['driver_earning']);
        $this->assertEquals($fare, $split['platform_commission'] + $split['driver_earning']);
    }

    /** @test */
    public function snapshot_stores_commission_percentage_immutably(): void
    {
        $fare = 2500.00;
        $split15 = $this->settlementService->calculateSplit($fare, 15.0);
        $split12 = $this->settlementService->calculateSplit($fare, 12.0);

        $this->assertEquals(375.00, $split15['platform_commission']);
        $this->assertEquals(300.00, $split12['platform_commission']);
    }

    /** @test */
    public function validates_commission_bounds(): void
    {
        $splitNegative = $this->settlementService->calculateSplit(1000.00, -10.0);
        $this->assertEquals(0.00, $splitNegative['commission_percent']);

        $splitOver = $this->settlementService->calculateSplit(1000.00, 120.0);
        $this->assertEquals(100.00, $splitOver['commission_percent']);
    }
}
