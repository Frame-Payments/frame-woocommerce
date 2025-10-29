<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Invoices\DeletedResponse;
use Frame\Models\Products\Product;
use Frame\Models\Products\ProductCreateRequest;
use Frame\Models\Products\ProductListResponse;
use Frame\Models\Products\ProductUpdateRequest;

final class Products
{
    private const BASE_PATH = '/v1/products';

    public function create(ProductCreateRequest $params): Product
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return Product::fromArray($json);
    }

    public function update(string $id, ProductUpdateRequest $params): Product
    {
        $json = Client::update(self::BASE_PATH . "/{$id}", $params->toArray());

        return Product::fromArray($json);
    }

    public function retrieve(string $id): Product
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return Product::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1): ProductListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);

        return ProductListResponse::fromArray($json);
    }

    public function search(?string $name = null, ?bool $active = null, ?bool $shippable = null): ProductListResponse
    {
        $json = Client::get(self::BASE_PATH, ['name' => $name, 'active' => $active, 'shippable' => $shippable]);

        return ProductListResponse::fromArray($json);
    }

    public function delete(string $id): DeletedResponse
    {
        $json = Client::delete(self::BASE_PATH . "/{$id}");

        return DeletedResponse::fromArray($json);
    }
}
