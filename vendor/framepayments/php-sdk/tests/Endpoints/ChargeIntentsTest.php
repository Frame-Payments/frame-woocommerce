<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\ChargeIntents;
use Frame\Models\ChargeIntents\ChargeIntent;
use Frame\Models\ChargeIntents\ChargeIntentCreateRequest;
use Frame\Models\ChargeIntents\ChargeIntentListResponse;
use Frame\Models\ChargeIntents\ChargeIntentUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class ChargeIntentsTest extends TestCase
{
    private $chargeIntentsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->chargeIntentsEndpoint = new ChargeIntents();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new ChargeIntentCreateRequest(amount: 2000, currency: 'usd');
        $sampleChargeIntentData = $this->getSampleChargeIntentData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/charge_intents', $createRequest->toArray())
            ->andReturn($sampleChargeIntentData);

        $chargeIntent = $this->chargeIntentsEndpoint->create($createRequest);

        $this->assertInstanceOf(ChargeIntent::class, $chargeIntent);
        $this->assertEquals($sampleChargeIntentData['id'], $chargeIntent->id);
    }

    public function testUpdate()
    {
        $intentId = 'ci_123';
        $updateRequest = new ChargeIntentUpdateRequest(amount: 3000);
        $sampleChargeIntentData = $this->getSampleChargeIntentData();
        $sampleChargeIntentData['amount'] = 3000;

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/charge_intents/{$intentId}", $updateRequest->toArray())
            ->andReturn($sampleChargeIntentData);

        $chargeIntent = $this->chargeIntentsEndpoint->update($intentId, $updateRequest);

        $this->assertInstanceOf(ChargeIntent::class, $chargeIntent);
        $this->assertEquals(3000, $chargeIntent->amount);
    }

    public function testRetrieve()
    {
        $intentId = 'ci_123';
        $sampleChargeIntentData = $this->getSampleChargeIntentData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/charge_intents/{$intentId}")
            ->andReturn($sampleChargeIntentData);

        $chargeIntent = $this->chargeIntentsEndpoint->retrieve($intentId);

        $this->assertInstanceOf(ChargeIntent::class, $chargeIntent);
        $this->assertEquals($sampleChargeIntentData['id'], $chargeIntent->id);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleChargeIntentData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/charge_intents', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->chargeIntentsEndpoint->list();

        $this->assertInstanceOf(ChargeIntentListResponse::class, $response);
        $this->assertCount(1, $response->chargeIntents);
    }

    public function testConfirm()
    {
        $intentId = 'ci_123';
        $sampleChargeIntentData = $this->getSampleChargeIntentData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/charge_intents/{$intentId}/confirm", [])
            ->andReturn($sampleChargeIntentData);

        $chargeIntent = $this->chargeIntentsEndpoint->confirm($intentId);
        $this->assertInstanceOf(ChargeIntent::class, $chargeIntent);
    }

    public function testCapture()
    {
        $intentId = 'ci_123';
        $sampleChargeIntentData = $this->getSampleChargeIntentData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/charge_intents/{$intentId}/capture", [])
            ->andReturn($sampleChargeIntentData);

        $chargeIntent = $this->chargeIntentsEndpoint->capture($intentId);
        $this->assertInstanceOf(ChargeIntent::class, $chargeIntent);
    }

    public function testCancel()
    {
        $intentId = 'ci_123';
        $sampleChargeIntentData = $this->getSampleChargeIntentData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/charge_intents/{$intentId}/cancel", [])
            ->andReturn($sampleChargeIntentData);

        $chargeIntent = $this->chargeIntentsEndpoint->cancel($intentId);
        $this->assertInstanceOf(ChargeIntent::class, $chargeIntent);
    }
}
