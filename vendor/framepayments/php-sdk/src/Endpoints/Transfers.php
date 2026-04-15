<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class Transfers
{
    private const BASE_PATH = '/v1/transfers';

    public function list(int $perPage = 10, int $page = 1): array
    {
        return Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);
    }

    public function retrieve(string $id): array
    {
        return Client::get(self::BASE_PATH . "/{$id}");
    }

    public function create(array $params): array
    {
        return Client::post(self::BASE_PATH, $params);
    }
}
