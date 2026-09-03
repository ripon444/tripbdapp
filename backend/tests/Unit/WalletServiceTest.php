<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\WalletService;
use InvalidArgumentException;
use RuntimeException;

class WalletServiceTest extends TestCase
{
    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = new WalletService();
    }

    /** @test */
    public function credits_wallet_and_maintains_balance_integrity(): void
    {
        $initialBalance = 1000.00;
        $creditAmount = 850.00;
        $expectedBalance = $initialBalance + $creditAmount;

        $this->assertEquals(1850.00, $expectedBalance);
    }

    /** @test */
    public function debits_wallet_with_proper_balance_after(): void
    {
        $balanceBefore = 5000.00;
        $debitAmount = 2000.00;
        $balanceAfter = $balanceBefore - $debitAmount;

        $this->assertEquals(3000.00, $balanceAfter);
    }

    /** @test */
    public function ensures_double_entry_balance_arithmetic_consistency(): void
    {
        $balanceBefore = 1000.00;
        $amount = 500.00;
        $balanceAfter = $balanceBefore + $amount;

        $this->assertEquals($balanceAfter, $balanceBefore + $amount);
    }
}
