<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WalletService
{
    /**
     * Get or create wallet for a driver.
     */
    public function getOrCreateWallet(int $driverId): Wallet
    {
        return Wallet::firstOrCreate(
            ['driver_id' => $driverId],
            ['balance' => 0.00]
        );
    }

    /**
     * Credit driver wallet.
     *
     * @param int $driverId
     * @param float $amount
     * @param string $type ('earning', 'adjustment', 'bonus', 'refund')
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $description
     * @return WalletTransaction
     */
    public function credit(
        int $driverId,
        float $amount,
        string $type = 'earning',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Credit amount must be greater than zero. Received: {$amount}");
        }

        $wallet = $this->getOrCreateWallet($driverId);
        $balanceBefore = (float) $wallet->balance;
        $balanceAfter = round($balanceBefore + $amount, 2);

        $wallet->balance = $balanceAfter;
        $wallet->save();

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => round($amount, 2),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description ?? "Wallet credit of ৳" . number_format($amount, 2),
            'balance_after' => $balanceAfter,
        ]);
    }

    /**
     * Debit driver wallet.
     *
     * @param int $driverId
     * @param float $amount
     * @param string $type ('withdrawal', 'commission', 'adjustment', 'penalty')
     * @param string|null $referenceType
     * @param int|null $referenceId
     * @param string|null $description
     * @return WalletTransaction
     */
    public function debit(
        int $driverId,
        float $amount,
        string $type = 'withdrawal',
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Debit amount must be greater than zero. Received: {$amount}");
        }

        $wallet = $this->getOrCreateWallet($driverId);
        $balanceBefore = (float) $wallet->balance;

        if ($balanceBefore < $amount && $type === 'withdrawal') {
            throw new RuntimeException("Insufficient wallet balance for withdrawal. Available: ৳{$balanceBefore}, Requested: ৳{$amount}");
        }

        $balanceAfter = round($balanceBefore - $amount, 2);
        $wallet->balance = $balanceAfter;
        $wallet->save();

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => $type,
            'amount' => -round($amount, 2),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description ?? "Wallet debit of ৳" . number_format($amount, 2),
            'balance_after' => $balanceAfter,
        ]);
    }

    /**
     * Get summary metrics for driver wallet.
     */
    public function getWalletSummary(int $driverId): array
    {
        $wallet = $this->getOrCreateWallet($driverId);
        $transactions = WalletTransaction::where('wallet_id', $wallet->id)->get();

        $totalEarnings = $transactions->where('type', 'earning')->where('amount', '>', 0)->sum('amount');
        $totalWithdrawals = abs($transactions->where('type', 'withdrawal')->sum('amount'));

        return [
            'wallet_id' => $wallet->id,
            'driver_id' => $driverId,
            'current_balance' => (float) $wallet->balance,
            'total_earnings' => (float) $totalEarnings,
            'total_withdrawals' => (float) $totalWithdrawals,
            'currency' => 'BDT',
        ];
    }
}
