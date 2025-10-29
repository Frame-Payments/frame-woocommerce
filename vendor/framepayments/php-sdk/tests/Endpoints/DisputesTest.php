<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Disputes;
use Frame\Models\Disputes\Dispute;
use Frame\Models\Disputes\DisputeListResponse;
use Frame\Tests\TestCase;
use Mockery;

class DisputesTest extends TestCase
{
    private $disputesEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->disputesEndpoint = new Disputes();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRetrieve()
    {
        $disputeId = 'dis_test123';
        $sampleDisputeData = $this->getSampleDisputeData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/disputes/{$disputeId}")
            ->andReturn($sampleDisputeData);

        $dispute = $this->disputesEndpoint->retrieve($disputeId);

        $this->assertInstanceOf(Dispute::class, $dispute);
        $this->assertEquals($sampleDisputeData['id'], $dispute->id);


    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleDisputeData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/disputes', ['per_page' => 10, 'page' => 1, 'charge' => null, 'charge_intent' => null])
            ->andReturn($sampleListData);

        $response = $this->disputesEndpoint->list();

        $this->assertInstanceOf(DisputeListResponse::class, $response);
        $this->assertCount(1, $response->disputes);
    }
}
