<?php

namespace Frame;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response
{
    private $response;

    public function __construct(GuzzleResponse $response)
    {
        $this->response = $response;
    }

    public function getStatusCode()
    {
        return $this->response->getStatusCode();
    }

    public function getHeaders()
    {
        return $this->response->getHeaders();
    }

    public function getBody()
    {
        $body = $this->response->getBody()->getContents();
        $this->response->getBody()->rewind(); // Rewind the stream for further reads

        return $body;
    }

    public function toArray()
    {
        return json_decode($this->getBody(), true);
    }

    public function toObject()
    {
        return json_decode($this->getBody()); // Ensure JSON decoding matches the API's response format
    }


    public function handleResponse()
    {
        $statusCode = $this->getStatusCode();

        if ($statusCode >= 400) {
            throw Exception::fromResponse($this->toArray());
        }

        return $this;
    }
}
