<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class Capabilities
{
    public function list(string $accountId): array
    {
        return Client::get("/v1/accounts/{$accountId}/capabilities");
    }

    public function request(string $accountId, array $params): array
    {
        return Client::post("/v1/accounts/{$accountId}/capabilities", $params);
    }

    public function retrieve(string $accountId, string $name): array
    {
        return Client::get("/v1/accounts/{$accountId}/capabilities/{$name}");
    }

    public function disable(string $accountId, string $name): array
    {
        return Client::delete("/v1/accounts/{$accountId}/capabilities/{$name}");
    }
}
