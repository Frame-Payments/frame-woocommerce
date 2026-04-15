<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Onboarding;
use Frame\Models\Onboarding\OnboardingSession;
use Frame\Models\Onboarding\OnboardingSessionCreateRequest;
use Frame\Models\Onboarding\OnboardingSessionListResponse;
use Frame\Models\Onboarding\OnboardingSessionUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class OnboardingTest extends TestCase
{
    private Onboarding $onboardingEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->onboardingEndpoint = new Onboarding();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleOnboardingSessionData()],
            'has_more' => false,
            'object' => 'list',
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/onboarding/sessions', [])
            ->andReturn($sampleListData);

        $response = $this->onboardingEndpoint->list();

        $this->assertInstanceOf(OnboardingSessionListResponse::class, $response);
        $this->assertCount(1, $response->sessions);
        $this->assertFalse($response->hasMore);
    }

    public function testListWithParams()
    {
        $params = ['customer_id' => 'cus_123', 'status' => 'in_progress'];
        $sampleListData = [
            'data' => [$this->getSampleOnboardingSessionData()],
            'has_more' => false,
            'object' => 'list',
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/onboarding/sessions', $params)
            ->andReturn($sampleListData);

        $response = $this->onboardingEndpoint->list($params);

        $this->assertInstanceOf(OnboardingSessionListResponse::class, $response);
        $this->assertCount(1, $response->sessions);
    }

    public function testCreate()
    {
        $createRequest = new OnboardingSessionCreateRequest(customerId: 'cus_123');
        $sampleSessionData = $this->getSampleOnboardingSessionData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/onboarding/sessions', $createRequest->toArray())
            ->andReturn($sampleSessionData);

        $session = $this->onboardingEndpoint->create($createRequest);

        $this->assertInstanceOf(OnboardingSession::class, $session);
        $this->assertEquals($sampleSessionData['id'], $session->id);
        $this->assertEquals('cus_123', $session->customerId);
    }

    public function testRetrieve()
    {
        $sessionId = 'os_test123';
        $sampleSessionData = $this->getSampleOnboardingSessionData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/onboarding/sessions/{$sessionId}")
            ->andReturn($sampleSessionData);

        $session = $this->onboardingEndpoint->retrieve($sessionId);

        $this->assertInstanceOf(OnboardingSession::class, $session);
        $this->assertEquals($sampleSessionData['id'], $session->id);
    }

    public function testUpdate()
    {
        $sessionId = 'os_test123';
        $updateRequest = new OnboardingSessionUpdateRequest(status: 'completed');
        $sampleSessionData = $this->getSampleOnboardingSessionData();
        $sampleSessionData['status'] = 'completed';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/onboarding/sessions/{$sessionId}", $updateRequest->toArray())
            ->andReturn($sampleSessionData);

        $session = $this->onboardingEndpoint->update($sessionId, $updateRequest);

        $this->assertInstanceOf(OnboardingSession::class, $session);
        $this->assertEquals('completed', $session->status);
    }

    public function testPayout()
    {
        $sessionId = 'os_test123';
        $params = ['payout_method_id' => 'pm_123'];
        $sampleSessionData = $this->getSampleOnboardingSessionData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/onboarding/sessions/{$sessionId}/payout", $params)
            ->andReturn($sampleSessionData);

        $session = $this->onboardingEndpoint->payout($sessionId, $params);

        $this->assertInstanceOf(OnboardingSession::class, $session);
        $this->assertEquals($sampleSessionData['id'], $session->id);
    }
}
