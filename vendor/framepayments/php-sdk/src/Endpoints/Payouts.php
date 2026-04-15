<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class Payouts
{
    private const BASE_PATH = '/v1/payouts';

    public function create(array $params): array
    {
        return Client::post(self::BASE_PATH, $params);
    }
}
