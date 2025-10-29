<?php

namespace Frame;

use GuzzleHttp\Psr7\Request as GuzzleRequest;

class Request
{
    private $method;
    private $uri;
    private $headers = [];
    private $body;

    public function __construct($method, $uri, $body = [], $headers = [])
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->body = $body;
        $this->headers = $headers;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function toGuzzleRequest()
    {
        return new GuzzleRequest($this->method, $this->uri, $this->headers, json_encode($this->body));
    }
}
