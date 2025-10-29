<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\InvoiceLineItems;
use Frame\Models\InvoiceLineItems\InvoiceLineItem;
use Frame\Models\InvoiceLineItems\LineItemCreateRequest;
use Frame\Models\InvoiceLineItems\LineItemListResponse;
use Frame\Models\InvoiceLineItems\LineItemUpdateRequest;
use Frame\Models\Invoices\DeletedResponse;
use Frame\Tests\TestCase;
use Mockery;

class InvoiceLineItemsTest extends TestCase
{
    private $lineItemsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->lineItemsEndpoint = new InvoiceLineItems();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $invoiceId = 'invoice_123';
        $createRequest = new LineItemCreateRequest(product: 'item_123', quantity: 0);
        $sampleInvoiceLineItemData = $this->getSampleInvoiceLineItemData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/invoices/{$invoiceId}/line_items", $createRequest->toArray())
            ->andReturn($sampleInvoiceLineItemData);

        $invoiceLineItem = $this->lineItemsEndpoint->create($invoiceId, $createRequest);

        $this->assertInstanceOf(InvoiceLineItem::class, $invoiceLineItem);
        $this->assertEquals($sampleInvoiceLineItemData['id'], $invoiceLineItem->id);
    }

    public function testUpdate()
    {
        $invoiceId = 'invoice_123';
        $lineItemId = 'lineItem_123';
        $updateRequest = new LineItemUpdateRequest(product: null, quantity: 10);

        $sampleInvoiceLineItemData = $this->getSampleInvoiceLineItemData();
        $sampleInvoiceLineItemData['quantity'] = 10;

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/invoices/{$invoiceId}/line_items/{$lineItemId}", $updateRequest->toArray())
            ->andReturn($sampleInvoiceLineItemData);

        $invoiceLineItem = $this->lineItemsEndpoint->update($invoiceId, $lineItemId, $updateRequest);

        $this->assertInstanceOf(InvoiceLineItem::class, $invoiceLineItem);
        $this->assertEquals(10, $invoiceLineItem->quantity);
    }

    public function testRetrieve()
    {
        $invoiceId = 'invoice_123';
        $lineItemId = 'lineItem_123';
        $sampleInvoiceLineItemData = $this->getSampleInvoiceLineItemData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/invoices/{$invoiceId}/line_items/{$lineItemId}")
            ->andReturn($sampleInvoiceLineItemData);

        $invoiceLineItem = $this->lineItemsEndpoint->retrieve($invoiceId, $lineItemId);

        $this->assertInstanceOf(InvoiceLineItem::class, $invoiceLineItem);
        $this->assertEquals($sampleInvoiceLineItemData['id'], $invoiceLineItem->id);
    }

    public function testDelete()
    {
        $invoiceId = 'invoice_123';
        $lineItemId = 'lineItem_123';
        $sampleDeletedData = $this->getSampleDeletedResponse();

        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("/v1/invoices/{$invoiceId}/line_items/{$lineItemId}")
            ->andReturn($sampleDeletedData);

        $invoiceDeleteResponse = $this->lineItemsEndpoint->delete($invoiceId, $lineItemId);
        $this->assertInstanceOf(DeletedResponse::class, $invoiceDeleteResponse);
    }

    public function testList()
    {
        $invoiceId = 'invoice_123';
        $sampleListData = [
            'data' => [$this->getSampleInvoiceLineItemData()],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/invoices/{$invoiceId}/line_items", ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->lineItemsEndpoint->list($invoiceId);

        $this->assertInstanceOf(LineItemListResponse::class, $response);
        $this->assertCount(1, $response->lineItems);
    }
}
