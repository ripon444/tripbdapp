<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\AdminLog;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\FinancialSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PaymentController extends Controller
{
    protected PaymentGatewayManager $gatewayManager;
    protected FinancialSettlementService $settlementService;

    public function __construct(PaymentGatewayManager $gatewayManager, FinancialSettlementService $settlementService)
    {
        $this->gatewayManager = $gatewayManager;
        $this->settlementService = $settlementService;
    }

    /**
     * Initialize / Create a payment for a booking.
     * POST /api/v1/payments/create
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|integer|exists:bookings,id',
            'payment_method' => 'required|string|in:cash,bkash,nagad,rocket,card',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $booking = Booking::findOrFail($request->booking_id);

        // Ownership validation: Customer can only pay for their own booking unless admin
        if ($user && $user->role === 'customer' && $booking->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only make payments for your own bookings.'
            ], 403);
        }

        // Idempotency check via header or existing pending payment
        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');
        if ($idempotencyKey) {
            $existingPayment = Payment::where('idempotency_key', $idempotencyKey)->first();
            if ($existingPayment) {
                return response()->json([
                    'success' => true,
                    'message' => 'Idempotent request: returning existing payment record.',
                    'data' => $existingPayment
                ]);
            }
        }

        // Check if booking already paid
        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This booking has already been paid.'
            ], 400);
        }

        $amount = (float) ($booking->final_fare ?? $booking->estimated_fare ?? 0);
        $method = $request->payment_method;
        $transactionId = 'TRP-PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        // Create initial payment record
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'amount' => $amount,
            'currency' => 'BDT',
            'payment_method' => $method,
            'transaction_id' => $transactionId,
            'gateway' => $method,
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'booking_number' => $booking->booking_number,
            ]
        ]);

        $gateway = $this->gatewayManager->getGateway($method);
        $gatewayResult = $gateway->createPayment([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'currency' => 'BDT',
            'customer_phone' => $booking->customer ? $booking->customer->phone : '',
            'customer_name' => $booking->customer ? $booking->customer->name : '',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment session initialized successfully.',
            'data' => [
                'payment' => $payment,
                'gateway_response' => $gatewayResult,
            ]
        ], 201);
    }

    /**
     * View payment details.
     * GET /api/v1/payments/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $payment = Payment::with(['booking', 'customer'])->findOrFail($id);
        $user = $request->user();

        if ($user && $user->role === 'customer' && $payment->customer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to this payment record.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Customer payment history.
     * GET /api/v1/customer/payments
     */
    public function customerPayments(Request $request): JsonResponse
    {
        $user = $request->user();
        $payments = Payment::where('customer_id', $user->id)
            ->with('booking')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Driver trip payment history.
     * GET /api/v1/driver/payments
     */
    public function driverPayments(Request $request): JsonResponse
    {
        $user = $request->user();
        $payments = Payment::whereHas('booking', function ($query) use ($user) {
            $query->where('driver_id', $user->id);
        })->with('booking')->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Admin all payments list.
     * GET /api/v1/admin/payments
     */
    public function adminPayments(Request $request): JsonResponse
    {
        $query = Payment::with(['booking', 'customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Admin refund processing.
     * POST /api/v1/payments/{payment}/refund
     */
    public function refund(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:1',
            'reason' => 'required|string|max:500',
            'type' => 'nullable|in:full,partial',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        if ($user && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators are authorized to process refunds.'
            ], 403);
        }

        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'paid' && $payment->status !== 'partially_refunded') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed (paid) transactions are eligible for refund.'
            ], 400);
        }

        $refundAmount = $request->filled('amount') ? floatval($request->amount) : floatval($payment->amount);
        if ($refundAmount > $payment->amount) {
            return response()->json([
                'success' => false,
                'message' => "Refund amount (৳{$refundAmount}) exceeds original paid amount (৳{$payment->amount})."
            ], 422);
        }

        $refundType = ($refundAmount >= $payment->amount) ? 'full' : 'partial';
        $refundRef = 'REF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'customer_id' => $payment->customer_id,
            'refund_reference' => $refundRef,
            'amount' => $refundAmount,
            'type' => $refundType,
            'reason' => $request->reason,
            'status' => 'processed',
            'approved_by' => $user ? $user->id : null,
            'approved_at' => now(),
            'processed_at' => now(),
        ]);

        $payment->status = ($refundType === 'full') ? 'refunded' : 'partially_refunded';
        $payment->refunded_at = now();
        $payment->save();

        AdminLog::create([
            'admin_id' => $user ? $user->id : 1,
            'action' => 'process_refund',
            'target_type' => 'Payment',
            'target_id' => $payment->id,
            'description' => "Authorized {$refundType} refund of ৳{$refundAmount} for Payment #{$payment->transaction_id}. Reason: {$request->reason}",
            'ip_address' => $request->ip() ?? '127.0.0.1',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Refund of ৳{$refundAmount} processed successfully.",
            'data' => [
                'refund' => $refund,
                'payment' => $payment,
            ]
        ]);
    }

    /**
     * bKash Webhook Callback
     * POST /api/v1/payments/bkash/callback
     */
    public function bkashCallback(Request $request): JsonResponse
    {
        $paymentId = $request->input('paymentID');
        $payment = Payment::where('transaction_id', $paymentId)
            ->orWhere('id', $request->input('payment_id'))
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        // Webhook Idempotency: Ignore if already paid
        if ($payment->status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Payment already processed.']);
        }

        $gateway = $this->gatewayManager->getGateway('bkash');
        $result = $gateway->executePayment($payment->transaction_id, $request->all());

        if ($result['success']) {
            $payment->status = 'paid';
            $payment->gateway_transaction_id = $result['gateway_transaction_id'] ?? ('BK_TRX_' . uniqid());
            $payment->paid_at = now();
            $payment->save();

            $booking = Booking::find($payment->booking_id);
            if ($booking) {
                $booking->payment_status = 'paid';
                $booking->save();
            }

            return response()->json(['success' => true, 'message' => 'bKash payment verified successfully.', 'data' => $payment]);
        }

        $payment->status = 'failed';
        $payment->failed_at = now();
        $payment->save();

        return response()->json(['success' => false, 'message' => 'bKash verification failed.'], 400);
    }

    /**
     * Nagad Webhook Callback
     * POST /api/v1/payments/nagad/callback
     */
    public function nagadCallback(Request $request): JsonResponse
    {
        $paymentId = $request->input('payment_ref_id') ?? $request->input('payment_id');
        $payment = Payment::where('transaction_id', $paymentId)->orWhere('id', $paymentId)->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Payment already processed.']);
        }

        $gateway = $this->gatewayManager->getGateway('nagad');
        $result = $gateway->executePayment($payment->transaction_id, $request->all());

        if ($result['success']) {
            $payment->status = 'paid';
            $payment->gateway_transaction_id = $result['gateway_transaction_id'] ?? ('NG_TRX_' . uniqid());
            $payment->paid_at = now();
            $payment->save();

            $booking = Booking::find($payment->booking_id);
            if ($booking) {
                $booking->payment_status = 'paid';
                $booking->save();
            }

            return response()->json(['success' => true, 'message' => 'Nagad payment verified.', 'data' => $payment]);
        }

        $payment->status = 'failed';
        $payment->failed_at = now();
        $payment->save();

        return response()->json(['success' => false, 'message' => 'Nagad payment verification failed.'], 400);
    }

    /**
     * Rocket Webhook Callback
     * POST /api/v1/payments/rocket/callback
     */
    public function rocketCallback(Request $request): JsonResponse
    {
        $payment = Payment::where('transaction_id', $request->input('payment_id'))->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Already paid.']);
        }

        $gateway = $this->gatewayManager->getGateway('rocket');
        $result = $gateway->executePayment($payment->transaction_id, $request->all());

        if ($result['success']) {
            $payment->status = 'paid';
            $payment->gateway_transaction_id = $result['gateway_transaction_id'] ?? ('RK_TRX_' . uniqid());
            $payment->paid_at = now();
            $payment->save();

            return response()->json(['success' => true, 'data' => $payment]);
        }

        return response()->json(['success' => false, 'message' => 'Rocket callback failed.'], 400);
    }

    /**
     * Card Webhook / IPN
     * POST /api/v1/payments/card/webhook
     */
    public function cardWebhook(Request $request): JsonResponse
    {
        $payment = Payment::where('transaction_id', $request->input('tran_id') ?? $request->input('payment_id'))->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['success' => true, 'message' => 'Already paid.']);
        }

        $gateway = $this->gatewayManager->getGateway('card');
        $result = $gateway->executePayment($payment->transaction_id, $request->all());

        if ($result['success']) {
            $payment->status = 'paid';
            $payment->gateway_transaction_id = $result['gateway_transaction_id'] ?? ('CARD_TXN_' . uniqid());
            $payment->paid_at = now();
            $payment->save();

            $booking = Booking::find($payment->booking_id);
            if ($booking) {
                $booking->payment_status = 'paid';
                $booking->save();
            }

            return response()->json(['success' => true, 'data' => $payment]);
        }

        return response()->json(['success' => false, 'message' => 'Card verification failed.'], 400);
    }
}
