<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Accounts\Account;
use Frame\Models\Accounts\AccountCreateRequest;
use Frame\Models\Accounts\AccountListResponse;
use Frame\Models\Accounts\AccountUpdateRequest;
use Frame\Models\PaymentMethods\PaymentMethodListResponse;

final class Accounts
{
    private const BASE_PATH = '/v1/accounts';

    public function list(int $perPage = 10, int $page = 1): AccountListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page]);

        return AccountListResponse::fromArray($json);
    }

    public function create(AccountCreateRequest $params): Account
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return Account::fromArray($json);
    }

    public function retrieve(string $id): Account
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return Account::fromArray($json);
    }

    public function update(string $id, AccountUpdateRequest $params): Account
    {
        $json = Client::update(self::BASE_PATH . "/{$id}", $params->toArray());

        return Account::fromArray($json);
    }

    public function disable(string $id): Account
    {
        $json = Client::delete(self::BASE_PATH . "/{$id}");

        return Account::fromArray($json);
    }

    public function search(array $params = []): AccountListResponse
    {
        $json = Client::get(self::BASE_PATH . '/search', $params);

        return AccountListResponse::fromArray($json);
    }

    public function restrict(string $id): Account
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/restrict");

        return Account::fromArray($json);
    }

    public function unrestrict(string $id): Account
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/unrestrict");

        return Account::fromArray($json);
    }

    public function getPlaidLinkToken(string $id): array
    {
        return Client::get(self::BASE_PATH . "/{$id}/plaid_link_token");
    }

    public function getPaymentMethods(string $id, int $perPage = 10, int $page = 1): PaymentMethodListResponse
    {
        $json = Client::get(self::BASE_PATH . "/{$id}/payment_methods", ['per_page' => $perPage, 'page' => $page]);

        return PaymentMethodListResponse::fromArray($json);
    }

    public function geoCompliance(string $id): array
    {
        return Client::get(self::BASE_PATH . "/{$id}/geo_compliance");
    }
}
