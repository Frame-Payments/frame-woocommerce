<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Refunds\Refund;
use Frame\Models\Refunds\RefundCreateRequest;
use Frame\Models\Refunds\RefundListResponse;

final class Refunds
{
    private const BASE_PATH = '/v1/refunds';

    public function create(RefundCreateRequest $params): Refund
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return Refund::fromArray($json);
    }

    public function retrieve(string $id): Refund
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return Refund::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1): RefundListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);

        return RefundListResponse::fromArray($json);
    }

    public function cancel(string $id): Refund
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/cancel", []);

        return Refund::fromArray($json);
    }
}
