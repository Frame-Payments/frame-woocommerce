<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\PaymentMethods;
use Frame\Models\PaymentMethods\PaymentMethod;
use Frame\Models\PaymentMethods\PaymentMethodCreateACHRequest;
use Frame\Models\PaymentMethods\PaymentMethodCreateCardRequest;
use Frame\Models\PaymentMethods\PaymentMethodListResponse;
use Frame\Models\PaymentMethods\PaymentMethodType;
use Frame\Models\PaymentMethods\PaymentMethodUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class PaymentMethodsTest extends TestCase
{
    private $paymentMethodsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->paymentMethodsEndpoint = new PaymentMethods();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateACH()
    {
        $createRequest = new PaymentMethodCreateACHRequest(type: PaymentMethodType::ACH, customer: null, accountType: 'checking', accountNumber: 'XXXXXXXX', routingNumber: "XXXXXXXXX", billing: null);
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/payment_methods', $createRequest->toArray())
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->createBank($createRequest);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals($samplePaymentMethodData['id'], $paymentMethod->id);
    }

    public function testCreateCard()
    {
        $createRequest = new PaymentMethodCreateCardRequest(type: PaymentMethodType::CARD, customer: null, cardNumber: 'XXXXXXXX', expMonth: "XX", expYear: "XX", cvc: 'XXX', billing: null);
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/payment_methods', $createRequest->toArray())
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->createCard($createRequest);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals($samplePaymentMethodData['id'], $paymentMethod->id);
    }

    public function testUpdate()
    {
        $methodId = 'method_123';
        $updateRequest = new PaymentMethodUpdateRequest(expMonth: '02', expYear: '2088', billing: null);

        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/payment_methods/{$methodId}", $updateRequest->toArray())
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->update($methodId, $updateRequest);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
    }

    public function testRetrieveForCustomer()
    {
        $customerId = 'cus_123';
        $sampleListData = [
            'data' => [$this->getSamplePaymentMethodData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/customers/{$customerId}/payment_methods", ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->paymentMethodsEndpoint->retrieveForCustomer($customerId);

        $this->assertInstanceOf(PaymentMethodListResponse::class, $response);
        $this->assertCount(1, $response->methods);
    }

    public function testRetrieve()
    {
        $methodId = 'method_123';
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/payment_methods/{$methodId}")
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->retrieve($methodId);

        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals($samplePaymentMethodData['id'], $paymentMethod->id);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSamplePaymentMethodData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/payment_methods', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->paymentMethodsEndpoint->list();

        $this->assertInstanceOf(PaymentMethodListResponse::class, $response);
        $this->assertCount(1, $response->methods);
    }

    public function testAttach()
    {
        $methodId = 'method_123';
        $customerId = 'cus_123';
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();
        $samplePaymentMethodData['customer'] = 'cus_123';

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/payment_methods/{$methodId}/attach", ['customer' => $customerId])
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->attach($methodId, $customerId);
        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
        $this->assertEquals($samplePaymentMethodData['customer'], $paymentMethod->customer);
    }

    public function testDetach()
    {
        $methodId = 'method_123';
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/payment_methods/{$methodId}/detach")
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->detach($methodId);
        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
    }

    public function testBlock()
    {
        $methodId = 'method_123';
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/payment_methods/{$methodId}/block")
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->block($methodId);
        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
    }

    public function testUnblock()
    {
        $methodId = 'method_123';
        $samplePaymentMethodData = $this->getSamplePaymentMethodData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/payment_methods/{$methodId}/unblock")
            ->andReturn($samplePaymentMethodData);

        $paymentMethod = $this->paymentMethodsEndpoint->unblock($methodId);
        $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
    }
}
