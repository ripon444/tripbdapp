<?php

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /**
     * Get unique identifier slug for the payment gateway.
     */
    public function getIdentifier(): string;

    /**
     * Check if gateway is configured with necessary API credentials.
     */
    public function isConfigured(): bool;

    /**
     * Check if currently running in sandbox test mode.
     */
    public function isSandbox(): bool;

    /**
     * Create / initialize a payment transaction.
     *
     * @param array $payload ['booking_id', 'amount', 'currency', 'customer_phone', 'customer_name', 'callback_url']
     * @return array ['success' => bool, 'transaction_id' => string, 'gateway_reference' => ?string, 'payment_url' => ?string, 'message' => string]
     */
    public function createPayment(array $payload): array;

    /**
     * Execute / verify payment callback after user authorization.
     *
     * @param string $paymentId
     * @param array $callbackData
     * @return array ['success' => bool, 'status' => string, 'transaction_id' => string, 'amount' => float, 'message' => string]
     */
    public function executePayment(string $paymentId, array $callbackData = []): array;

    /**
     * Query status of an existing payment transaction directly from the gateway server.
     *
     * @param string $transactionId
     * @return array
     */
    public function queryPayment(string $transactionId): array;

    /**
     * Initiate a full or partial refund.
     *
     * @param string $transactionId
     * @param float $amount
     * @param string $reason
     * @return array ['success' => bool, 'refund_id' => ?string, 'status' => string, 'message' => string]
     */
    public function refundPayment(string $transactionId, float $amount, string $reason = ''): array;
}
