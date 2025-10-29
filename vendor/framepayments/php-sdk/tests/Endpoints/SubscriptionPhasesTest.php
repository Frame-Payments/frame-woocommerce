<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\SubscriptionPhases;
use Frame\Models\Invoices\DeletedResponse;
use Frame\Models\SubscriptionPhases\PhaseBulkUpdateRequest;
use Frame\Models\SubscriptionPhases\PhaseCreateRequest;
use Frame\Models\SubscriptionPhases\PhaseListResponse;
use Frame\Models\SubscriptionPhases\PhasePricingType;
use Frame\Models\SubscriptionPhases\PhaseUpdateRequest;
use Frame\Models\SubscriptionPhases\SubscriptionPhase;
use Frame\Tests\TestCase;
use Mockery;

class SubscriptionPhasesTest extends TestCase
{
    private $subscriptionPhasesEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->subscriptionPhasesEndpoint = new SubscriptionPhases();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testList()
    {
        $subscriptionId = 'sub_123';
        $sampleListData = [
            'phases' => [$this->getSamplePhaseData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/phases/")
            ->andReturn($sampleListData);

        $response = $this->subscriptionPhasesEndpoint->list($subscriptionId);

        $this->assertInstanceOf(PhaseListResponse::class, $response);
        $this->assertCount(1, $response->phases);
    }

    public function testCreate()
    {
        $subscriptionId = 'sub_123';
        $createRequest = new PhaseCreateRequest(ordinal: 10, pricingType: PhasePricingType::STATIC, name: 'New Phase', amountCents: 10000, discountPercentage: null, periodCount: null);
        $samplePhaseData = $this->getSamplePhaseData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/phases/", $createRequest->toArray())
            ->andReturn($samplePhaseData);

        $phase = $this->subscriptionPhasesEndpoint->create($subscriptionId, $createRequest);

        $this->assertInstanceOf(SubscriptionPhase::class, $phase);
        $this->assertEquals($samplePhaseData['id'], $phase->id);
    }

    public function testUpdate()
    {
        $subscriptionId = 'sub_123';
        $phaseId = 'phase_123';
        $updateRequest = new PhaseUpdateRequest(name: 'Phase Updated');

        $samplePhaseData = $this->getSamplePhaseData();
        $samplePhaseData['name'] = 'Phase Updated';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/phases/{$phaseId}", $updateRequest->toArray())
            ->andReturn($samplePhaseData);

        $phase = $this->subscriptionPhasesEndpoint->update($subscriptionId, $phaseId, $updateRequest);

        $this->assertInstanceOf(SubscriptionPhase::class, $phase);
        $this->assertEquals($samplePhaseData['name'], $phase->name);
    }

    public function testRetrieve()
    {
        $subscriptionId = 'sub_123';
        $phaseId = 'phase_123';
        $samplePhaseData = $this->getSamplePhaseData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/phases/{$phaseId}")
            ->andReturn($samplePhaseData);

        $phase = $this->subscriptionPhasesEndpoint->retrieve($subscriptionId, $phaseId);

        $this->assertInstanceOf(SubscriptionPhase::class, $phase);
        $this->assertEquals($samplePhaseData['id'], $phase->id);
    }

    public function testDelete()
    {
        $subscriptionId = 'sub_123';
        $phaseId = 'phase_123';
        $samplePhaseData = $this->getSampleDeletedResponse();

        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/phases/{$phaseId}")
            ->andReturn($samplePhaseData);

        $response = $this->subscriptionPhasesEndpoint->delete($subscriptionId, $phaseId);
        $this->assertInstanceOf(DeletedResponse::class, $response);
    }

    public function testBulkUpdate()
    {
        $subscriptionId = 'sub_123';
        $updateRequest = [new PhaseBulkUpdateRequest(id: 'phase_123', name: 'New Phase'),];
        $expectedPayload = array_map(fn (PhaseBulkUpdateRequest $p) => $p->toArray(), $updateRequest);
        $sampleListData = [
            'phases' => [$this->getSamplePhaseData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/phases/bulk_update", ['phases' => $expectedPayload])
            ->andReturn($sampleListData);

        $response = $this->subscriptionPhasesEndpoint->bulkUpdate($subscriptionId, $updateRequest);

        $this->assertInstanceOf(PhaseListResponse::class, $response);
        $this->assertCount(1, $response->phases);
    }
}
