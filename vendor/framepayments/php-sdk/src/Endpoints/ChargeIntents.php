<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\ChargeIntents\ChargeIntent;
use Frame\Models\ChargeIntents\ChargeIntentCreateRequest;
use Frame\Models\ChargeIntents\ChargeIntentListResponse;
use Frame\Models\ChargeIntents\ChargeIntentUpdateRequest;

final class ChargeIntents
{
    private const BASE_PATH = '/v1/charge_intents';

    public function create(ChargeIntentCreateRequest $params): ChargeIntent
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return ChargeIntent::fromArray($json);
    }

    public function update(string $id, ChargeIntentUpdateRequest $params): ChargeIntent
    {
        $json = Client::update(self::BASE_PATH . "/{$id}", $params->toArray());

        return ChargeIntent::fromArray($json);
    }

    public function retrieve(string $id): ChargeIntent
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return ChargeIntent::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1): ChargeIntentListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);

        return ChargeIntentListResponse::fromArray($json);
    }

    public function confirm(string $id): ChargeIntent
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/confirm", []);

        return ChargeIntent::fromArray($json);
    }

    public function capture(string $id): ChargeIntent
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/capture", []);

        return ChargeIntent::fromArray($json);
    }

    public function cancel(string $id): ChargeIntent
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/cancel", []);

        return ChargeIntent::fromArray($json);
    }
}
