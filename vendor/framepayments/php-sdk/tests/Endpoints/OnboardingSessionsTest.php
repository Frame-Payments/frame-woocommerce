<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\OnboardingSessions;
use Frame\Models\OnboardingSessions\OnboardingSession;
use Frame\Models\OnboardingSessions\OnboardingSessionCreateRequest;
use Frame\Models\OnboardingSessions\OnboardingSessionListResponse;
use Frame\Tests\TestCase;
use Mockery;

class OnboardingSessionsTest extends TestCase
{
    private OnboardingSessions $onboardingSessionsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->onboardingSessionsEndpoint = new OnboardingSessions();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new OnboardingSessionCreateRequest(
            accountId: 'acct_test123',
            returnUrl: 'https://example.com/return',
            steps: ['identity', 'banking'],
        );
        $sampleSessionData = $this->getSampleAccountOnboardingSessionData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/onboarding_sessions', $createRequest->toArray())
            ->andReturn($sampleSessionData);

        $session = $this->onboardingSessionsEndpoint->create($createRequest);

        $this->assertInstanceOf(OnboardingSession::class, $session);
        $this->assertEquals($sampleSessionData['id'], $session->id);
        $this->assertEquals('acct_test123', $session->accountId);
        $this->assertEquals('https://example.com/return', $session->returnUrl);
    }

    public function testList()
    {
        $accountId = 'acct_test123';
        $sampleListData = [
            'data' => [$this->getSampleAccountOnboardingSessionData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/onboarding_sessions', ['account_id' => $accountId])
            ->andReturn($sampleListData);

        $response = $this->onboardingSessionsEndpoint->list($accountId);

        $this->assertInstanceOf(OnboardingSessionListResponse::class, $response);
        $this->assertCount(1, $response->sessions);
        $this->assertEquals($sampleListData['data'][0]['id'], $response->sessions[0]->id);
    }
}
