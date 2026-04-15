<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class SubscriptionChangeLogs
{
    public function list(string $subscriptionId): array
    {
        return Client::get("/v1/subscriptions/{$subscriptionId}/change_logs");
    }
}
