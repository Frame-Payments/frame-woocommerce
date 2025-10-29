<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Invoices;
use Frame\Models\Invoices\DeletedResponse;
use Frame\Models\Invoices\Invoice;
use Frame\Models\Invoices\InvoiceCollectionMethod;
use Frame\Models\Invoices\InvoiceCreateRequest;
use Frame\Models\Invoices\InvoiceListResponse;
use Frame\Models\Invoices\InvoiceUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class InvoicesTest extends TestCase
{
    private $invoicesEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->invoicesEndpoint = new Invoices();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new InvoiceCreateRequest(customer: 'cust_123', collectionMethod: InvoiceCollectionMethod::AUTO_CHARGE);
        $sampleInvoiceData = $this->getSampleInvoiceData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/invoices', $createRequest->toArray())
            ->andReturn($sampleInvoiceData);

        $invoice = $this->invoicesEndpoint->create($createRequest);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($sampleInvoiceData['id'], $invoice->id);
    }

    public function testUpdate()
    {
        $invoiceId = 'inv_123';
        $updateRequest = new InvoiceUpdateRequest(description: 'Updated invoice');

        $sampleInvoiceData = $this->getSampleInvoiceData();
        $sampleInvoiceData['description'] = 'Updated invoice';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/invoices/{$invoiceId}", $updateRequest->toArray())
            ->andReturn($sampleInvoiceData);

        $invoice = $this->invoicesEndpoint->update($invoiceId, $updateRequest);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('Updated invoice', $invoice->description);
    }

    public function testRetrieve()
    {
        $invoiceId = 'inv_123';
        $sampleInvoiceData = $this->getSampleInvoiceData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/invoices/{$invoiceId}")
            ->andReturn($sampleInvoiceData);

        $invoice = $this->invoicesEndpoint->retrieve($invoiceId);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($sampleInvoiceData['id'], $invoice->id);
    }

    public function testDelete()
    {
        $invoiceId = 'inv_123';
        $sampleInvoiceData = $this->getSampleDeletedResponse();

        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("/v1/invoices/{$invoiceId}")
            ->andReturn($sampleInvoiceData);

        $invoice = $this->invoicesEndpoint->delete($invoiceId);
        $this->assertInstanceOf(DeletedResponse::class, $invoice);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleInvoiceData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/invoices', ['per_page' => 10, 'page' => 1, 'customer' => null, 'status' => null])
            ->andReturn($sampleListData);

        $response = $this->invoicesEndpoint->list();

        $this->assertInstanceOf(InvoiceListResponse::class, $response);
        $this->assertCount(1, $response->invoices);
    }

    public function testIssue()
    {
        $invoiceId = 'inv_123';
        $sampleInvoiceData = $this->getSampleInvoiceData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/invoices/{$invoiceId}/issue")
            ->andReturn($sampleInvoiceData);

        $invoice = $this->invoicesEndpoint->issue($invoiceId);

        $this->assertInstanceOf(Invoice::class, $invoice);
    }
}
