<?php

namespace Frame\Tests\Unit;

use Frame\Request;
use Frame\Tests\TestCase;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class RequestTest extends TestCase
{
    public function testConstructor()
    {
        $method = 'POST';
        $uri = '/v1/customers';
        $body = ['name' => 'John Doe'];
        $headers = ['Content-Type' => 'application/json'];

        $request = new Request($method, $uri, $body, $headers);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
        $this->assertEquals($body, $request->getBody());
        $this->assertEquals($headers, $request->getHeaders());
    }

    public function testConstructorWithDefaults()
    {
        $method = 'GET';
        $uri = '/v1/customers';

        $request = new Request($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
        $this->assertEquals([], $request->getBody());
        $this->assertEquals([], $request->getHeaders());
    }

    public function testToGuzzleRequest()
    {
        $method = 'POST';
        $uri = '/v1/customers';
        $body = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $headers = ['Content-Type' => 'application/json'];

        $request = new Request($method, $uri, $body, $headers);
        $guzzleRequest = $request->toGuzzleRequest();

        $this->assertInstanceOf(GuzzleRequest::class, $guzzleRequest);
        $this->assertEquals($method, $guzzleRequest->getMethod());
        $this->assertEquals($uri, $guzzleRequest->getUri());
        $this->assertEquals(['Content-Type' => ['application/json']], $guzzleRequest->getHeaders());
        $this->assertEquals(json_encode($body), $guzzleRequest->getBody()->getContents());
    }

    public function testToGuzzleRequestWithEmptyBody()
    {
        $method = 'GET';
        $uri = '/v1/customers';

        $request = new Request($method, $uri);
        $guzzleRequest = $request->toGuzzleRequest();

        $this->assertEquals('[]', $guzzleRequest->getBody()->getContents());
    }
}
