<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Disputes\Dispute;
use Frame\Models\Disputes\DisputeListResponse;

final class Disputes
{
    private const BASE_PATH = '/v1/disputes';

    public function retrieve(string $id): Dispute
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return Dispute::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1, ?string $charge = null, ?string $chargeIntent = null): DisputeListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page , 'charge' => $charge, 'charge_intent' => $chargeIntent]);

        return DisputeListResponse::fromArray($json);
    }
}
