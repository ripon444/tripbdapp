<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Payments\CashPaymentGateway;
use App\Services\Payments\BkashPaymentGateway;
use App\Services\Payments\NagadPaymentGateway;
use App\Services\Payments\RocketPaymentGateway;
use App\Services\Payments\CardPaymentGateway;
use App\Services\Payments\PaymentGatewayManager;

class PaymentGatewayTest extends TestCase
{
    /** @test */
    public function cash_gateway_initializes_without_external_credentials(): void
    {
        $gateway = new CashPaymentGateway();
        $this->assertTrue($gateway->isConfigured());
        $this->assertEquals('cash', $gateway->getIdentifier());

        $result = $gateway->createPayment(['booking_id' => 1, 'amount' => 500.00]);
        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);
    }

    /** @test */
    public function bkash_gateway_operates_in_sandbox_mode(): void
    {
        $gateway = new BkashPaymentGateway();
        $this->assertEquals('bkash', $gateway->getIdentifier());

        $result = $gateway->createPayment(['booking_id' => 1, 'amount' => 1200.00]);
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
    }

    /** @test */
    public function nagad_gateway_operates_in_sandbox_mode(): void
    {
        $gateway = new NagadPaymentGateway();
        $this->assertEquals('nagad', $gateway->getIdentifier());

        $result = $gateway->createPayment(['booking_id' => 1, 'amount' => 1500.00]);
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['transaction_id']);
    }

    /** @test */
    public function rocket_gateway_provides_clean_adapter_abstraction(): void
    {
        $gateway = new RocketPaymentGateway();
        $this->assertEquals('rocket', $gateway->getIdentifier());
        $this->assertTrue($gateway->isSandbox());
    }

    /** @test */
    public function card_gateway_supports_pci_compliant_hosted_checkout(): void
    {
        $gateway = new CardPaymentGateway();
        $this->assertEquals('card', $gateway->getIdentifier());

        $result = $gateway->createPayment(['booking_id' => 1, 'amount' => 3000.00]);
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['payment_url']);
    }
}
