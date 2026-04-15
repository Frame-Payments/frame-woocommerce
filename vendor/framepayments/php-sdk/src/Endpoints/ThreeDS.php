<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class ThreeDS
{
    private const BASE_PATH = '/v1/3ds/intents';

    public function create(array $params): array
    {
        return Client::post(self::BASE_PATH, $params);
    }

    public function retrieve(string $id): array
    {
        return Client::get(self::BASE_PATH . "/{$id}");
    }

    public function resend(string $id): array
    {
        return Client::post(self::BASE_PATH . "/{$id}/resend", []);
    }
}
