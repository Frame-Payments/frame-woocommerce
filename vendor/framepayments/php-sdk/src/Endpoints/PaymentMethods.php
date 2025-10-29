<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\PaymentMethods\PaymentMethod;
use Frame\Models\PaymentMethods\PaymentMethodCreateACHRequest;
use Frame\Models\PaymentMethods\PaymentMethodCreateCardRequest;
use Frame\Models\PaymentMethods\PaymentMethodListResponse;
use Frame\Models\PaymentMethods\PaymentMethodUpdateRequest;

final class PaymentMethods
{
    private const BASE_PATH = '/v1/payment_methods';

    public function createCard(PaymentMethodCreateCardRequest $params): PaymentMethod
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return PaymentMethod::fromArray($json);
    }

    public function createBank(PaymentMethodCreateACHRequest $params): PaymentMethod
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return PaymentMethod::fromArray($json);
    }

    public function update(string $id, PaymentMethodUpdateRequest $params): PaymentMethod
    {
        $json = Client::update(self::BASE_PATH . "/{$id}", $params->toArray());

        return PaymentMethod::fromArray($json);
    }

    public function retrieve(string $id): PaymentMethod
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return PaymentMethod::fromArray($json);
    }

    public function retrieveForCustomer(string $customer, int $perPage = 10, int $page = 1): PaymentMethodListResponse
    {
        $json = Client::get("/v1/customers/{$customer}/payment_methods", ['per_page' => $perPage, 'page' => $page]);

        return PaymentMethodListResponse::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1): PaymentMethodListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);

        return PaymentMethodListResponse::fromArray($json);
    }

    public function attach(string $id, string $customer): PaymentMethod
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/attach", ['customer' => $customer]);

        return PaymentMethod::fromArray($json);
    }

    public function detach(string $id): PaymentMethod
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/detach");

        return PaymentMethod::fromArray($json);
    }

    public function block(string $id): PaymentMethod
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/block");

        return PaymentMethod::fromArray($json);
    }

    public function unblock(string $id): PaymentMethod
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/unblock");

        return PaymentMethod::fromArray($json);
    }
}
