<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class ProductPhases
{
    public function list(string $productId): array
    {
        return Client::get("/v1/products/{$productId}/phases");
    }

    public function create(string $productId, array $params): array
    {
        return Client::post("/v1/products/{$productId}/phases", $params);
    }

    public function retrieve(string $productId, string $phaseId): array
    {
        return Client::get("/v1/products/{$productId}/phases/{$phaseId}");
    }

    public function update(string $productId, string $phaseId, array $params): array
    {
        return Client::update("/v1/products/{$productId}/phases/{$phaseId}", $params);
    }

    public function delete(string $productId, string $phaseId): array
    {
        return Client::delete("/v1/products/{$productId}/phases/{$phaseId}");
    }

    public function bulkUpdate(string $productId, array $phases): array
    {
        return Client::update("/v1/products/{$productId}/phases/bulk_update", ['phases' => $phases]);
    }
}
