<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class Charges
{
    private const BASE_PATH = '/v1/charges';

    public function retrieve(string $id): array
    {
        return Client::get(self::BASE_PATH . "/{$id}");
    }

    public function trace(string $id): array
    {
        return Client::get(self::BASE_PATH . "/{$id}/trace");
    }
}
