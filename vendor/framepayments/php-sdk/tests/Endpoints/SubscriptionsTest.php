<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Subscriptions;
use Frame\Models\Subscriptions\Subscription;
use Frame\Models\Subscriptions\SubscriptionCreateRequest;
use Frame\Models\Subscriptions\SubscriptionListResponse;
use Frame\Models\Subscriptions\SubscriptionSearchRequest;
use Frame\Models\Subscriptions\SubscriptionStatus;
use Frame\Models\Subscriptions\SubscriptionUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class SubscriptionsTest extends TestCase
{
    private $subscriptionsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->subscriptionsEndpoint = new Subscriptions();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new SubscriptionCreateRequest(product: 'prod_123', currency: 'usd', customer: 'cus_123', defaultPaymentMethod: 'method_123', description: null, metadata: []);
        $sampleSubscriptionData = $this->getSampleSubscriptionData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/subscriptions', $createRequest->toArray())
            ->andReturn($sampleSubscriptionData);

        $subscription = $this->subscriptionsEndpoint->create($createRequest);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($sampleSubscriptionData['id'], $subscription->id);
    }

    public function testUpdate()
    {
        $subscriptionId = 'sub_123';
        $updateRequest = new SubscriptionUpdateRequest(description: 'Updated subscription');

        $sampleSubscriptionData = $this->getSampleSubscriptionData();
        $sampleSubscriptionData['description'] = 'Updated subscription';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}", $updateRequest->toArray())
            ->andReturn($sampleSubscriptionData);

        $subscription = $this->subscriptionsEndpoint->update($subscriptionId, $updateRequest);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('Updated subscription', $subscription->description);
    }

    public function testRetrieve()
    {
        $subscriptionId = 'sub_123';
        $sampleSubscriptionData = $this->getSampleSubscriptionData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}")
            ->andReturn($sampleSubscriptionData);

        $subscription = $this->subscriptionsEndpoint->retrieve($subscriptionId);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($sampleSubscriptionData['id'], $subscription->id);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleSubscriptionData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/subscriptions', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->subscriptionsEndpoint->list();

        $this->assertInstanceOf(SubscriptionListResponse::class, $response);
        $this->assertCount(1, $response->subscriptions);
    }

    public function testSearch()
    {
        $searchRequest = new SubscriptionSearchRequest(status: SubscriptionStatus::ACTIVE);
        $sampleListData = [
            'data' => [$this->getSampleSubscriptionData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/subscriptions/search', $searchRequest->toArray())
            ->andReturn($sampleListData);

        $response = $this->subscriptionsEndpoint->search($searchRequest);
        $this->assertInstanceOf(SubscriptionListResponse::class, $response);
    }

    public function testCancel()
    {
        $subscriptionId = 'sub_123';
        $sampleSubscriptionData = $this->getSampleSubscriptionData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/subscriptions/{$subscriptionId}/cancel", [])
            ->andReturn($sampleSubscriptionData);

        $subscription = $this->subscriptionsEndpoint->cancel($subscriptionId);
        $this->assertInstanceOf(Subscription::class, $subscription);
    }
}
