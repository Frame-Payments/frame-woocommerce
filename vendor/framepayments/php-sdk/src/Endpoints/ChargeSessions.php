<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class ChargeSessions
{
    private const BASE_PATH = '/v1/charge_sessions';

    public function create(array $params): array
    {
        return Client::post(self::BASE_PATH, $params);
    }

    public function update(string $id, array $params): array
    {
        return Client::update(self::BASE_PATH . "/{$id}", $params);
    }
}
