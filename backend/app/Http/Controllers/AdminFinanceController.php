<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\FinancialSettlement;
use App\Models\Withdrawal;
use App\Models\Refund;
use App\Models\SystemSetting;
use App\Models\AdminLog;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminFinanceController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Financial aggregated analytics report.
     * GET /api/v1/admin/reports/financial
     */
    public function financialReport(Request $request): JsonResponse
    {
        $period = $request->input('period', 'all'); // 'daily', 'weekly', 'monthly', 'all'
        
        $settlementsQuery = FinancialSettlement::where('status', 'settled');
        $paymentsQuery = Payment::where('status', 'paid');
        $refundsQuery = Refund::where('status', 'processed');
        $withdrawalsQuery = Withdrawal::where('status', 'completed');

        if ($period === 'daily') {
            $settlementsQuery->whereDate('created_at', today());
            $paymentsQuery->whereDate('created_at', today());
            $refundsQuery->whereDate('created_at', today());
            $withdrawalsQuery->whereDate('created_at', today());
        } elseif ($period === 'weekly') {
            $settlementsQuery->where('created_at', '>=', now()->subDays(7));
            $paymentsQuery->where('created_at', '>=', now()->subDays(7));
            $refundsQuery->where('created_at', '>=', now()->subDays(7));
            $withdrawalsQuery->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === 'monthly') {
            $settlementsQuery->where('created_at', '>=', now()->subDays(30));
            $paymentsQuery->where('created_at', '>=', now()->subDays(30));
            $refundsQuery->where('created_at', '>=', now()->subDays(30));
            $withdrawalsQuery->where('created_at', '>=', now()->subDays(30));
        }

        $grossBookingValue = (float) $settlementsQuery->sum('gross_fare');
        $platformCommission = (float) $settlementsQuery->sum('platform_commission_amount');
        $driverEarnings = (float) $settlementsQuery->sum('driver_earning_amount');

        $cashPayments = (float) Payment::where('status', 'paid')->where('payment_method', 'cash')->sum('amount');
        $onlinePayments = (float) Payment::where('status', 'paid')->where('payment_method', '!=', 'cash')->sum('amount');

        $totalRefunds = (float) $refundsQuery->sum('amount');
        $totalWithdrawals = (float) $withdrawalsQuery->sum('amount');

        $netPlatformRevenue = round($platformCommission - $totalRefunds, 2);

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'currency' => 'BDT',
                'gross_booking_value' => round($grossBookingValue, 2),
                'platform_commission' => round($platformCommission, 2),
                'driver_earnings' => round($driverEarnings, 2),
                'cash_payments' => round($cashPayments, 2),
                'online_payments' => round($onlinePayments, 2),
                'total_refunds' => round($totalRefunds, 2),
                'total_withdrawals_paid' => round($totalWithdrawals, 2),
                'net_platform_revenue' => $netPlatformRevenue,
                'generated_at' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Manual Driver Wallet Adjustment (Bonus or Penalty).
     * POST /api/v1/admin/drivers/{driver}/wallet-adjustment
     */
    public function walletAdjustment(Request $request, int $driverId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'type' => 'required|string|in:bonus,penalty,adjustment',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $amount = floatval($request->amount);
        $type = $request->type;
        $reason = $request->reason;

        if ($type === 'penalty') {
            $tx = $this->walletService->debit($driverId, $amount, 'adjustment', 'AdminAdjustment', $user ? $user->id : 1, "Admin penalty adjustment: {$reason}");
        } else {
            $tx = $this->walletService->credit($driverId, $amount, 'adjustment', 'AdminAdjustment', $user ? $user->id : 1, "Admin {$type}: {$reason}");
        }

        AdminLog::create([
            'admin_id' => $user ? $user->id : 1,
            'action' => 'wallet_adjustment',
            'target_type' => 'DriverWallet',
            'target_id' => $driverId,
            'description' => "Applied {$type} of ৳{$amount} to driver ID #{$driverId}. Reason: {$reason}",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Wallet {$type} adjustment of ৳{$amount} applied successfully.",
            'data' => $tx
        ]);
    }

    /**
     * Get financial system settings.
     * GET /api/v1/admin/settings/financial
     */
    public function getSettings(): JsonResponse
    {
        $keys = [
            'platform_commission_percent',
            'payment_required_before_trip',
            'cancellation_fee',
            'minimum_withdrawal',
            'maximum_withdrawal',
            'currency',
        ];

        $settings = SystemSetting::whereIn('key', $keys)->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => [
                'platform_commission_percent' => floatval($settings['platform_commission_percent'] ?? 15),
                'payment_required_before_trip' => ($settings['payment_required_before_trip'] ?? 'false') === 'true',
                'cancellation_fee' => floatval($settings['cancellation_fee'] ?? 100),
                'minimum_withdrawal' => floatval($settings['minimum_withdrawal'] ?? 500),
                'maximum_withdrawal' => floatval($settings['maximum_withdrawal'] ?? 50000),
                'currency' => $settings['currency'] ?? 'BDT',
            ]
        ]);
    }

    /**
     * Update financial system settings.
     * PUT /api/v1/admin/settings/financial
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform_commission_percent' => 'nullable|numeric|min:0|max:100',
            'payment_required_before_trip' => 'nullable|boolean',
            'cancellation_fee' => 'nullable|numeric|min:0',
            'minimum_withdrawal' => 'nullable|numeric|min:50',
            'maximum_withdrawal' => 'nullable|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if ($request->has('platform_commission_percent')) {
            SystemSetting::updateOrCreate(
                ['key' => 'platform_commission_percent'],
                ['value' => (string) $request->platform_commission_percent, 'type' => 'decimal', 'description' => 'Platform commission percentage']
            );
        }

        if ($request->has('payment_required_before_trip')) {
            SystemSetting::updateOrCreate(
                ['key' => 'payment_required_before_trip'],
                ['value' => $request->payment_required_before_trip ? 'true' : 'false', 'type' => 'boolean', 'description' => 'Enforce upfront payment before trip start']
            );
        }

        if ($request->has('cancellation_fee')) {
            SystemSetting::updateOrCreate(
                ['key' => 'cancellation_fee'],
                ['value' => (string) $request->cancellation_fee, 'type' => 'decimal', 'description' => 'Default trip cancellation fee in BDT']
            );
        }

        AdminLog::create([
            'admin_id' => $user ? $user->id : 1,
            'action' => 'update_financial_settings',
            'target_type' => 'SystemSetting',
            'target_id' => 1,
            'description' => "Updated financial settings: " . json_encode($request->all()),
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Financial system settings updated successfully.'
        ]);
    }

    /**
     * List all financial settlements and wallet transactions.
     * GET /api/v1/admin/transactions
     */
    public function listTransactions(Request $request): JsonResponse
    {
        $type = $request->input('type', 'settlements');

        if ($type === 'wallet') {
            $query = \App\Models\WalletTransaction::with('wallet.driver');
            if ($request->filled('tx_type')) {
                $query->where('type', $request->tx_type);
            }
            $transactions = $query->latest()->paginate(20);
            return response()->json(['success' => true, 'data' => $transactions]);
        }

        $query = FinancialSettlement::with(['booking', 'driver']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $settlements = $query->latest()->paginate(20);
        return response()->json(['success' => true, 'data' => $settlements]);
    }
}
