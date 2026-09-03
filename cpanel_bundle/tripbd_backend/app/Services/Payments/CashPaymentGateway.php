<?php

namespace App\Services\Payments;

class CashPaymentGateway implements PaymentGatewayInterface
{
    public function getIdentifier(): string
    {
        return 'cash';
    }

    public function isConfigured(): bool
    {
        return true; // Cash is always configured natively
    }

    public function isSandbox(): bool
    {
        return false;
    }

    public function createPayment(array $payload): array
    {
        return [
            'success' => true,
            'gateway' => 'cash',
            'status' => 'pending',
            'transaction_id' => 'CASH-' . strtoupper(uniqid()),
            'payment_url' => null,
            'message' => 'Cash on delivery initialized. Payment will be collected directly by driver upon destination arrival.'
        ];
    }

    public function executePayment(string $paymentId, array $callbackData = []): array
    {
        return [
            'success' => true,
            'status' => 'paid',
            'transaction_id' => $paymentId,
            'message' => 'Cash payment verified and collected.'
        ];
    }

    public function queryPayment(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'pending',
            'transaction_id' => $transactionId
        ];
    }

    public function refundPayment(string $transactionId, float $amount, string $reason = ''): array
    {
        return [
            'success' => true,
            'refund_id' => 'REF-CASH-' . strtoupper(uniqid()),
            'status' => 'processed',
            'message' => 'Cash adjustment or refund processed via manual driver/customer payout.'
        ];
    }
}
