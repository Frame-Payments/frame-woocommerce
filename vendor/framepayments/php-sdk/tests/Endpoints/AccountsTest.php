<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Accounts;
use Frame\Models\Accounts\Account;
use Frame\Models\Accounts\AccountCreateRequest;
use Frame\Models\Accounts\AccountListResponse;
use Frame\Models\Accounts\AccountType;
use Frame\Models\Accounts\AccountUpdateRequest;
use Frame\Models\PaymentMethods\PaymentMethodListResponse;
use Frame\Tests\TestCase;
use Mockery;

class AccountsTest extends TestCase
{
    private Accounts $accountsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->accountsEndpoint = new Accounts();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleAccountData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/accounts', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->accountsEndpoint->list();

        $this->assertInstanceOf(AccountListResponse::class, $response);
        $this->assertCount(1, $response->accounts);
    }

    public function testCreate()
    {
        $createRequest = new AccountCreateRequest(type: AccountType::INDIVIDUAL);
        $sampleAccountData = $this->getSampleAccountData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/accounts', $createRequest->toArray())
            ->andReturn($sampleAccountData);

        $account = $this->accountsEndpoint->create($createRequest);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($sampleAccountData['id'], $account->id);
        $this->assertEquals(AccountType::INDIVIDUAL, $account->type);
    }

    public function testRetrieve()
    {
        $accountId = 'acct_test123';
        $sampleAccountData = $this->getSampleAccountData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/accounts/{$accountId}")
            ->andReturn($sampleAccountData);

        $account = $this->accountsEndpoint->retrieve($accountId);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals($sampleAccountData['id'], $account->id);
    }

    public function testUpdate()
    {
        $accountId = 'acct_test123';
        $updateRequest = new AccountUpdateRequest(externalId: 'ext_123');
        $sampleAccountData = $this->getSampleAccountData();
        $sampleAccountData['external_id'] = 'ext_123';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/accounts/{$accountId}", $updateRequest->toArray())
            ->andReturn($sampleAccountData);

        $account = $this->accountsEndpoint->update($accountId, $updateRequest);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('ext_123', $account->externalId);
    }

    public function testDisable()
    {
        $accountId = 'acct_test123';
        $sampleAccountData = $this->getSampleAccountData();
        $sampleAccountData['status'] = 'disabled';

        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("/v1/accounts/{$accountId}")
            ->andReturn($sampleAccountData);

        $account = $this->accountsEndpoint->disable($accountId);

        $this->assertInstanceOf(Account::class, $account);
    }

    public function testSearch()
    {
        $params = ['type' => 'individual', 'status' => 'active'];
        $sampleListData = [
            'data' => [$this->getSampleAccountData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/accounts/search', $params)
            ->andReturn($sampleListData);

        $response = $this->accountsEndpoint->search($params);

        $this->assertInstanceOf(AccountListResponse::class, $response);
        $this->assertCount(1, $response->accounts);
    }

    public function testRestrict()
    {
        $accountId = 'acct_test123';
        $sampleAccountData = $this->getSampleAccountData();
        $sampleAccountData['status'] = 'restricted';

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/accounts/{$accountId}/restrict")
            ->andReturn($sampleAccountData);

        $account = $this->accountsEndpoint->restrict($accountId);

        $this->assertInstanceOf(Account::class, $account);
    }

    public function testUnrestrict()
    {
        $accountId = 'acct_test123';
        $sampleAccountData = $this->getSampleAccountData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with("/v1/accounts/{$accountId}/unrestrict")
            ->andReturn($sampleAccountData);

        $account = $this->accountsEndpoint->unrestrict($accountId);

        $this->assertInstanceOf(Account::class, $account);
    }

    public function testGetPlaidLinkToken()
    {
        $accountId = 'acct_test123';
        $responseData = ['link_token' => 'link-sandbox-abc123'];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/accounts/{$accountId}/plaid_link_token")
            ->andReturn($responseData);

        $result = $this->accountsEndpoint->getPlaidLinkToken($accountId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('link_token', $result);
        $this->assertEquals('link-sandbox-abc123', $result['link_token']);
    }

    public function testGetPaymentMethods()
    {
        $accountId = 'acct_test123';
        $responseData = [
            'data' => [$this->getSamplePaymentMethodData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/accounts/{$accountId}/payment_methods", ['per_page' => 10, 'page' => 1])
            ->andReturn($responseData);

        $result = $this->accountsEndpoint->getPaymentMethods($accountId);

        $this->assertInstanceOf(PaymentMethodListResponse::class, $result);
        $this->assertCount(1, $result->methods);
    }

    public function testGeoCompliance()
    {
        $accountId = 'acct_test123';
        $responseData = [
            'status' => 'clear',
            'sonar_session_id' => 'fps_123',
            'evaluated_at' => '2024-01-01T00:00:00Z',
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/accounts/{$accountId}/geo_compliance")
            ->andReturn($responseData);

        $result = $this->accountsEndpoint->geoCompliance($accountId);

        $this->assertIsArray($result);
        $this->assertEquals('clear', $result['status']);
    }
}
