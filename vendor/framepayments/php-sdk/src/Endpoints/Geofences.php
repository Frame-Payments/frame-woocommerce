<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class Geofences
{
    private const BASE_PATH = '/v1/geofences';

    public function list(int $perPage = 10, int $page = 1): array
    {
        return Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);
    }
}
