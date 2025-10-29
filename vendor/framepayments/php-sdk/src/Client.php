<?php

namespace Frame;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

final class Client
{
    private static $client;

    private static function getClient()
    {
        if (! self::$client) {
            self::$client = new GuzzleClient([
                'base_uri' => 'https://api.framepayments.com',
                'headers' => [
                    'User-Agent' => 'Frame PHP SDK 1.0.0',
                    'Authorization' => 'Bearer ' . Auth::getApiKey(),
                    'Accept' => 'application/json',
                ],
            ]);
        }

        return self::$client;
    }

    private static function request(string $method, string $endpoint, array $body = [])
    {
        $options = [];
        if (! empty($body)) {
            if ($method === 'GET') {
                $options['query'] = $body;
            } else {
                $options['json'] = $body;
                $options['headers']['Content-Type'] = 'application/json';
            }
        }

        try {
            $response = self::getClient()->request($method, $endpoint, $options);

            return json_decode((string)$response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $errorBody = json_decode((string)$response->getBody(), true);

                throw Exception::fromResponse($errorBody);
            }

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public static function post(string $endpoint, array $body = [])
    {
        return self::request('POST', $endpoint, $body);
    }

    public static function get(string $endpoint, array $query = [])
    {
        return self::request('GET', $endpoint, $query);
    }

    public static function update(string $endpoint, array $body = [])
    {
        return self::request('PATCH', $endpoint, $body);
    }

    public static function delete(string $endpoint, array $body = [])
    {
        return self::request('DELETE', $endpoint, $body);
    }
}
