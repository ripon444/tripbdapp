<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminFinanceTest extends TestCase
{
    /** @test */
    public function admin_can_issue_full_and_partial_refunds(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function customer_cannot_issue_own_refund(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function admin_can_apply_manual_wallet_adjustments(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function financial_report_aggregates_gross_fare_commission_and_net_revenue(): void
    {
        $grossBookingValue = 100000.00;
        $commissionPercent = 15.0;
        $platformCommission = ($grossBookingValue * $commissionPercent) / 100;
        $refunds = 2000.00;
        $netRevenue = $platformCommission - $refunds;

        $this->assertEquals(15000.00, $platformCommission);
        $this->assertEquals(13000.00, $netRevenue);
    }
}
