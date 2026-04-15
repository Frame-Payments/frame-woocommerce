<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class PhoneVerifications
{
    public function create(string $accountId, array $params): array
    {
        return Client::post("/v1/accounts/{$accountId}/phone_verifications", $params);
    }

    public function confirm(string $accountId, string $id, array $params = []): array
    {
        return Client::post("/v1/accounts/{$accountId}/phone_verifications/{$id}/confirm", $params);
    }
}
