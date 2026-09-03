<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerGateway(new CashPaymentGateway());
        $this->registerGateway(new BkashPaymentGateway());
        $this->registerGateway(new NagadPaymentGateway());
        $this->registerGateway(new RocketPaymentGateway());
        $this->registerGateway(new CardPaymentGateway());
    }

    public function registerGateway(PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$gateway->getIdentifier()] = $gateway;
    }

    public function getGateway(string $method): PaymentGatewayInterface
    {
        $normalized = strtolower(trim($method));
        if (!isset($this->gateways[$normalized])) {
            throw new InvalidArgumentException("Unsupported payment method [{$method}]. Supported: cash, bkash, nagad, rocket, card.");
        }
        return $this->gateways[$normalized];
    }

    public function getSupportedMethods(): array
    {
        return array_keys($this->gateways);
    }
}
