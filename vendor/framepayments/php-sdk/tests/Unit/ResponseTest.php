<?php

namespace Frame\Tests\Unit;

use Frame\Exception;
use Frame\Response;
use Frame\Tests\TestCase;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class ResponseTest extends TestCase
{
    public function testGetStatusCode()
    {
        $guzzleResponse = new GuzzleResponse(200, [], '{"success": true}');
        $response = new Response($guzzleResponse);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetHeaders()
    {
        $headers = ['Content-Type' => ['application/json']];
        $guzzleResponse = new GuzzleResponse(200, $headers, '{"success": true}');
        $response = new Response($guzzleResponse);

        $this->assertEquals($headers, $response->getHeaders());
    }

    public function testGetBody()
    {
        $body = '{"success": true, "data": "test"}';
        $guzzleResponse = new GuzzleResponse(200, [], $body);
        $response = new Response($guzzleResponse);

        $this->assertEquals($body, $response->getBody());
    }

    public function testToArray()
    {
        $body = '{"success": true, "data": {"id": 123}}';
        $guzzleResponse = new GuzzleResponse(200, [], $body);
        $response = new Response($guzzleResponse);

        $expected = ['success' => true, 'data' => ['id' => 123]];
        $this->assertEquals($expected, $response->toArray());
    }

    public function testToObject()
    {
        $body = '{"success": true, "data": {"id": 123}}';
        $guzzleResponse = new GuzzleResponse(200, [], $body);
        $response = new Response($guzzleResponse);

        $result = $response->toObject();
        $this->assertIsObject($result);
        $this->assertTrue($result->success);
        $this->assertEquals(123, $result->data->id);
    }

    public function testHandleResponseSuccess()
    {
        $guzzleResponse = new GuzzleResponse(200, [], '{"success": true}');
        $response = new Response($guzzleResponse);

        $result = $response->handleResponse();
        $this->assertInstanceOf(Response::class, $result);
    }

    public function testHandleResponseError()
    {
        $errorBody = '{"error": {"message": "Not found", "code": 404}}';
        $guzzleResponse = new GuzzleResponse(404, [], $errorBody);
        $response = new Response($guzzleResponse);

        $this->expectException(Exception::class);
        $response->handleResponse();
    }

    public function testGetBodyRewindsStream()
    {
        $body = '{"test": "data"}';
        $guzzleResponse = new GuzzleResponse(200, [], $body);
        $response = new Response($guzzleResponse);

        // Read body first time
        $firstRead = $response->getBody();
        $this->assertEquals($body, $firstRead);

        // Read body second time - should work because stream was rewound
        $secondRead = $response->getBody();
        $this->assertEquals($body, $secondRead);
    }
}
