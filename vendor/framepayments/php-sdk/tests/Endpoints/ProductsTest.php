<?php

namespace Frame\Tests\Endpoints;

use Frame\Client;
use Frame\Endpoints\Products;
use Frame\Models\Invoices\DeletedResponse;
use Frame\Models\Products\Product;
use Frame\Models\Products\ProductCreateRequest;
use Frame\Models\Products\ProductListResponse;
use Frame\Models\Products\ProductPurchaseType;
use Frame\Models\Products\ProductRecurringInterval;
use Frame\Models\Products\ProductUpdateRequest;
use Frame\Tests\TestCase;
use Mockery;

class ProductsTest extends TestCase
{
    private $productsEndpoint;
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for the Client class
        $this->mockClient = Mockery::mock('alias:' . Client::class);
        $this->productsEndpoint = new Products();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreate()
    {
        $createRequest = new ProductCreateRequest(name: 'New Product', description: 'newer product', defaultPrice: 100, purchaseType: ProductPurchaseType::RECURRING, recurringInterval: ProductRecurringInterval::MONTHLY, shippable: null, url: null, metadata: null);
        $sampleProductData = $this->getSampleProductData();

        $this->mockClient
            ->shouldReceive('post')
            ->once()
            ->with('/v1/products', $createRequest->toArray())
            ->andReturn($sampleProductData);

        $product = $this->productsEndpoint->create($createRequest);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($sampleProductData['id'], $product->id);
    }

    public function testUpdate()
    {
        $productId = 'prod_123';
        $updateRequest = new ProductUpdateRequest(name: 'Product Updated');

        $sampleProductData = $this->getSampleProductData();
        $sampleProductData['name'] = 'Product Updated';

        $this->mockClient
            ->shouldReceive('update')
            ->once()
            ->with("/v1/products/{$productId}", $updateRequest->toArray())
            ->andReturn($sampleProductData);

        $product = $this->productsEndpoint->update($productId, $updateRequest);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($sampleProductData['name'], $product->name);
    }

    public function testRetrieve()
    {
        $productId = 'prod_123';
        $sampleProductData = $this->getSampleProductData();

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with("/v1/products/{$productId}")
            ->andReturn($sampleProductData);

        $product = $this->productsEndpoint->retrieve($productId);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals($sampleProductData['id'], $product->id);
    }

    public function testDelete()
    {
        $productId = 'prod_123';
        $sampleProductData = $this->getSampleDeletedResponse();

        $this->mockClient
            ->shouldReceive('delete')
            ->once()
            ->with("/v1/products/{$productId}")
            ->andReturn($sampleProductData);

        $response = $this->productsEndpoint->delete($productId);
        $this->assertInstanceOf(DeletedResponse::class, $response);
    }

    public function testList()
    {
        $sampleListData = [
            'data' => [$this->getSampleProductData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/products', ['per_page' => 10, 'page' => 1])
            ->andReturn($sampleListData);

        $response = $this->productsEndpoint->list();

        $this->assertInstanceOf(ProductListResponse::class, $response);
        $this->assertCount(1, $response->products);
    }

    public function testSearch()
    {
        $sampleListData = [
            'data' => [$this->getSampleProductData()],
            'meta' => ['total_count' => 1],
        ];

        $this->mockClient
            ->shouldReceive('get')
            ->once()
            ->with('/v1/products', ['name' => 'prod', 'active' => true, 'shippable' => true])
            ->andReturn($sampleListData);

        $response = $this->productsEndpoint->search('prod', true, true);

        $this->assertInstanceOf(ProductListResponse::class, $response);
        $this->assertCount(1, $response->products);
    }
}
