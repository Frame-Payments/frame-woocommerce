<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class TermsOfService
{
    private const BASE_PATH = '/v1/terms_of_service';

    public function createToken(): array
    {
        return Client::post(self::BASE_PATH, []);
    }

    public function update(string $token, ?int $acceptedAt = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $params = array_filter([
            'token' => $token,
            'accepted_at' => $acceptedAt,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], fn ($v) => $v !== null);

        return Client::update(self::BASE_PATH, $params);
    }
}
