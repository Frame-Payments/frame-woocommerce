<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Subscriptions\Subscription;
use Frame\Models\Subscriptions\SubscriptionCreateRequest;
use Frame\Models\Subscriptions\SubscriptionListResponse;
use Frame\Models\Subscriptions\SubscriptionSearchRequest;
use Frame\Models\Subscriptions\SubscriptionUpdateRequest;

final class Subscriptions
{
    private const BASE_PATH = '/v1/subscriptions';

    public function create(SubscriptionCreateRequest $params): Subscription
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return Subscription::fromArray($json);
    }

    public function update(string $id, SubscriptionUpdateRequest $params): Subscription
    {
        $json = Client::update(self::BASE_PATH . "/{$id}", $params->toArray());

        return Subscription::fromArray($json);
    }

    public function retrieve(string $id): Subscription
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return Subscription::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1): SubscriptionListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);

        return SubscriptionListResponse::fromArray($json);
    }

    public function search(SubscriptionSearchRequest $params): SubscriptionListResponse
    {
        $json = Client::get(self::BASE_PATH . '/search', $params->toArray());

        return SubscriptionListResponse::fromArray($json);
    }

    public function cancel(string $id): Subscription
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/cancel", []);

        return Subscription::fromArray($json);
    }
}
