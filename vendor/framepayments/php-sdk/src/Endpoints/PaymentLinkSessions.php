<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class PaymentLinkSessions
{
    private const BASE_PATH = '/v1/payment_link_sessions';

    public function create(array $params): array
    {
        return Client::post(self::BASE_PATH, $params);
    }
}
