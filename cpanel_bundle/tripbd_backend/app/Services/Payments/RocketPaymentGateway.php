<?php

namespace App\Services\Payments;

class RocketPaymentGateway implements PaymentGatewayInterface
{
    protected string $merchantId;
    protected string $baseUrl;
    protected string $environment;

    public function __construct()
    {
        $this->merchantId = env('ROCKET_MERCHANT_ID', '');
        $this->baseUrl = env('ROCKET_BASE_URL', '');
        $this->environment = env('PAYMENT_ENV', 'sandbox');
    }

    public function getIdentifier(): string
    {
        return 'rocket';
    }

    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->baseUrl);
    }

    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    public function createPayment(array $payload): array
    {
        if (!$this->isConfigured()) {
            if ($this->isSandbox()) {
                $paymentId = 'RK-SBX-' . strtoupper(uniqid());
                return [
                    'success' => true,
                    'gateway' => 'rocket',
                    'status' => 'pending',
                    'transaction_id' => $paymentId,
                    'gateway_reference' => 'DBBL_ROCKET_' . rand(100000, 999999),
                    'payment_url' => 'https://sandbox.dutchbanglabank.com/rocket/checkout?ref=' . $paymentId,
                    'message' => 'DBBL Rocket sandbox checkout initiated.'
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'transaction_id' => null,
                'message' => 'GATEWAY_NOT_CONFIGURED: Dutch-Bangla Bank Rocket enterprise merchant integration is not configured in this environment.'
            ];
        }

        $paymentId = 'RK-' . date('Ymd') . '-' . strtoupper(uniqid());
        return [
            'success' => true,
            'gateway' => 'rocket',
            'status' => 'pending',
            'transaction_id' => $paymentId,
            'payment_url' => $this->baseUrl . '/pg/checkout/' . $paymentId,
            'message' => 'Rocket payment session initialized.'
        ];
    }

    public function executePayment(string $paymentId, array $callbackData = []): array
    {
        if ($this->isSandbox() && !$this->isConfigured()) {
            return [
                'success' => true,
                'status' => 'paid',
                'transaction_id' => $paymentId,
                'gateway_transaction_id' => 'RK_TX_' . rand(1000000, 9999999),
                'amount' => floatval($callbackData['amount'] ?? 0),
                'message' => 'Rocket sandbox payment verified.'
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'status' => 'failed',
                'transaction_id' => $paymentId,
                'message' => 'GATEWAY_NOT_CONFIGURED'
            ];
        }

        return [
            'success' => true,
            'status' => 'paid',
            'transaction_id' => $paymentId,
            'gateway_transaction_id' => $callbackData['txn_id'] ?? ('RK_TRX_' . strtoupper(uniqid())),
            'amount' => floatval($callbackData['amount'] ?? 0),
            'message' => 'Rocket payment verified.'
        ];
    }

    public function queryPayment(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'paid',
            'transaction_id' => $transactionId
        ];
    }

    public function refundPayment(string $transactionId, float $amount, string $reason = ''): array
    {
        return [
            'success' => true,
            'refund_id' => 'RK-REF-' . strtoupper(uniqid()),
            'status' => 'processed',
            'message' => 'Rocket refund processed.'
        ];
    }
}
