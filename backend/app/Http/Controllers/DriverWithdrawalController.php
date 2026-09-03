<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use App\Models\Wallet;
use App\Models\AdminLog;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DriverWithdrawalController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Driver requests a payout/withdrawal.
     * POST /api/v1/driver/withdrawals
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:100',
            'method' => 'required|string|in:bkash,nagad,rocket,bank_transfer',
            'account_number' => 'required|string|min:11|max:30',
            'bank_name' => 'nullable|string|max:100',
            'branch_name' => 'nullable|string|max:100',
            'routing_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $wallet = $this->walletService->getOrCreateWallet($user->id);

        $requestedAmount = floatval($request->amount);

        // Balance validation
        if ($wallet->balance < $requestedAmount) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient wallet balance. Available: ৳{$wallet->balance}, Requested: ৳{$requestedAmount}."
            ], 422);
        }

        $withdrawalNumber = 'WTH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $withdrawal = Withdrawal::create([
            'driver_id' => $user->id,
            'wallet_id' => $wallet->id,
            'withdrawal_number' => $withdrawalNumber,
            'amount' => $requestedAmount,
            'method' => $request->method,
            'account_number' => $request->account_number,
            'bank_name' => $request->bank_name,
            'branch_name' => $request->branch_name,
            'routing_number' => $request->routing_number,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully and is pending administrative review.',
            'data' => [
                'id' => $withdrawal->id,
                'withdrawal_number' => $withdrawal->withdrawal_number,
                'amount' => $withdrawal->amount,
                'method' => $withdrawal->method,
                'masked_account' => $withdrawal->masked_account_number,
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at,
            ]
        ], 201);
    }

    /**
     * Driver's own withdrawal history.
     * GET /api/v1/driver/withdrawals
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $withdrawals = Withdrawal::where('driver_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Mask account numbers in output
        $withdrawals->getCollection()->transform(function ($item) {
            $item->account_number = $item->masked_account_number;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Admin view all withdrawals.
     * GET /api/v1/admin/withdrawals
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Withdrawal::with('driver');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    /**
     * Admin approve withdrawal.
     * POST /api/v1/admin/withdrawals/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Only pending withdrawals can be approved. Current status: {$withdrawal->status}"
            ], 400);
        }

        $withdrawal->status = 'approved';
        $withdrawal->processed_by = $user ? $user->id : 1;
        $withdrawal->processed_at = now();
        $withdrawal->save();

        AdminLog::create([
            'admin_id' => $user ? $user->id : 1,
            'action' => 'approve_withdrawal',
            'target_type' => 'Withdrawal',
            'target_id' => $withdrawal->id,
            'description' => "Approved driver withdrawal #{$withdrawal->withdrawal_number} for ৳{$withdrawal->amount}.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal approved.',
            'data' => $withdrawal
        ]);
    }

    /**
     * Admin reject withdrawal.
     * POST /api/v1/admin/withdrawals/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status !== 'pending' && $withdrawal->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => "Cannot reject withdrawal with status {$withdrawal->status}."
            ], 400);
        }

        $withdrawal->status = 'rejected';
        $withdrawal->admin_note = $request->input('reason', 'Administrative rejection.');
        $withdrawal->processed_by = $user ? $user->id : 1;
        $withdrawal->processed_at = now();
        $withdrawal->save();

        AdminLog::create([
            'admin_id' => $user ? $user->id : 1,
            'action' => 'reject_withdrawal',
            'target_type' => 'Withdrawal',
            'target_id' => $withdrawal->id,
            'description' => "Rejected driver withdrawal #{$withdrawal->withdrawal_number}. Reason: {$withdrawal->admin_note}",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal rejected.',
            'data' => $withdrawal
        ]);
    }

    /**
     * Admin mark processing.
     * POST /api/v1/admin/withdrawals/{id}/process
     */
    public function process(Request $request, int $id): JsonResponse
    {
        $withdrawal = Withdrawal::findOrFail($id);
        if ($withdrawal->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Withdrawal must be approved before processing.'], 400);
        }

        $withdrawal->status = 'processing';
        $withdrawal->save();

        return response()->json(['success' => true, 'message' => 'Withdrawal marked as processing.', 'data' => $withdrawal]);
    }

    /**
     * Admin mark complete and deduct wallet balance.
     * POST /api/v1/admin/withdrawals/{id}/complete
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $withdrawal = Withdrawal::findOrFail($id);

        if ($withdrawal->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'Withdrawal is already completed.'], 400);
        }

        // Deduct from wallet securely
        $this->walletService->debit(
            $withdrawal->driver_id,
            $withdrawal->amount,
            'withdrawal',
            'Withdrawal',
            $withdrawal->id,
            "Payout completed via {$withdrawal->method} (#{$withdrawal->withdrawal_number})"
        );

        $withdrawal->status = 'completed';
        $withdrawal->completed_at = now();
        $withdrawal->save();

        AdminLog::create([
            'admin_id' => $user ? $user->id : 1,
            'action' => 'complete_withdrawal',
            'target_type' => 'Withdrawal',
            'target_id' => $withdrawal->id,
            'description' => "Completed payout for withdrawal #{$withdrawal->withdrawal_number} for ৳{$withdrawal->amount}.",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal completed and wallet debited.',
            'data' => $withdrawal
        ]);
    }
}
