<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Refunds;
use Frame\Models\Refunds\Refund;
use Frame\Models\Refunds\RefundCreateRequest;
use Frame\Models\Refunds\RefundListResponse;
use Frame\Models\Refunds\RefundReason;
use Frame\Tests\TestCase;
use Mockery;

class RefundsTest extends TestCase
{
    private $refundsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->refundsEndpoint = new Refunds();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new RefundCreateRequest(amount: 100, chargeIntent: 'charge_123', reason: RefundReason::FRAUDULENT);
        $sampleRefundData = $this->getSampleRefundData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/refunds', $createRequest->toArray())
            ->andReturn($sampleRefundData);

        $refund = $this->refundsEndpoint->create($createRequest);

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals($sampleRefundData['id'], $refund->id);
    }

    public function testRetrieve()
    {
        $refundId = 'ref_123';
        $sampleRefundData = $this->getSampleRefundData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/refunds/{$refundId}")
            ->andReturn($sampleRefundData);

        $refund = $this->refundsEndpoint->retrieve($refundId);

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertEquals($sampleRefundData['id'], $refund->id);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleRefundData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/refunds', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->refundsEndpoint->list();

        $this->assertInstanceOf(RefundListResponse::class, $response);
        $this->assertCount(1, $response->refunds);
    }

    public function testCancel()
    {
        $refundId = 'ref_123';
        $sampleRefundData = $this->getSampleRefundData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/refunds/{$refundId}/cancel", [])
            ->andReturn($sampleRefundData);

        $refund = $this->refundsEndpoint->cancel($refundId);
        $this->assertInstanceOf(Refund::class, $refund);
    }
}
