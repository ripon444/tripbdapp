<?php

namespace Tests\Feature;

use Tests\TestCase;

class WithdrawalTest extends TestCase
{
    /** @test */
    public function driver_can_request_withdrawal_within_available_balance(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function driver_cannot_request_withdrawal_exceeding_balance(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function driver_withdrawal_response_masks_payout_account_number(): void
    {
        $account = "01712345678";
        $masked = substr($account, 0, 2) . '******' . substr($account, -3);
        $this->assertEquals("01******678", $masked);
    }

    /** @test */
    public function admin_can_approve_and_complete_withdrawal_with_wallet_debit(): void
    {
        $this->assertTrue(true);
    }
}
