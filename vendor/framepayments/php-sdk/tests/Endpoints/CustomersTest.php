<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Customers;
use Frame\Models\Customers\Customer;
use Frame\Models\Customers\CustomerCreateRequest;
use Frame\Models\Customers\CustomerListResponse;
use Frame\Models\Customers\CustomerSearchRequest;
use Frame\Models\Customers\CustomerUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class CustomersTest extends TestCase
{
    private $customersEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->customersEndpoint = new Customers();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new CustomerCreateRequest(name: 'John Doe', email: 'john@example.com');
        $sampleCustomerData = $this->getSampleCustomerData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/customers', $createRequest->toArray())
            ->andReturn($sampleCustomerData);

        $customer = $this->customersEndpoint->create($createRequest);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($sampleCustomerData['id'], $customer->id);
    }

    public function testUpdate()
    {
        $customerId = 'cus_123';
        $updateRequest = new CustomerUpdateRequest(name: 'Jane Doe');

        $sampleCustomerData = $this->getSampleCustomerData();
        $sampleCustomerData['name'] = 'Jane Doe';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/customers/{$customerId}", $updateRequest->toArray())
            ->andReturn($sampleCustomerData);

        $customer = $this->customersEndpoint->update($customerId, $updateRequest);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('Jane Doe', $customer->name);
    }

    public function testRetrieve()
    {
        $customerId = 'cus_123';
        $sampleCustomerData = $this->getSampleCustomerData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/customers/{$customerId}")
            ->andReturn($sampleCustomerData);

        $customer = $this->customersEndpoint->retrieve($customerId);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($sampleCustomerData['id'], $customer->id);
    }

    public function testDelete()
    {
        $customerId = 'cus_123';
        $sampleCustomerData = $this->getSampleCustomerData();

        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("/v1/customers/{$customerId}")
            ->andReturn($sampleCustomerData);

        $customer = $this->customersEndpoint->delete($customerId);
        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleCustomerData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/customers', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->customersEndpoint->list();

        $this->assertInstanceOf(CustomerListResponse::class, $response);
        $this->assertCount(1, $response->customers);
    }

    public function testSearch()
    {
        $searchRequest = new CustomerSearchRequest(email: 'john@example.com');
        $sampleListData = [
            'data' => [$this->getSampleCustomerData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/customers', $searchRequest->toArray())
            ->andReturn($sampleListData);

        $response = $this->customersEndpoint->search($searchRequest);

        $this->assertInstanceOf(CustomerListResponse::class, $response);
    }

    public function testBlock()
    {
        $customerId = 'cus_123';
        $sampleCustomerData = $this->getSampleCustomerData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/customers/{$customerId}/block")
            ->andReturn($sampleCustomerData);

        $customer = $this->customersEndpoint->block($customerId);
        $this->assertInstanceOf(Customer::class, $customer);
    }

    public function testUnblock()
    {
        $customerId = 'cus_123';
        $sampleCustomerData = $this->getSampleCustomerData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/customers/{$customerId}/unblock")
            ->andReturn($sampleCustomerData);

        $customer = $this->customersEndpoint->unblock($customerId);
        $this->assertInstanceOf(Customer::class, $customer);
    }
}
