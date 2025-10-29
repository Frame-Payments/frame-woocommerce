<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\IdentityVerifications\CustomerIdentity;
use Frame\Models\IdentityVerifications\IdentityCreateRequest;

final class IdentityVerifications
{
    private const BASE_PATH = '/v1/customer_identity_verifications';

    public function create(IdentityCreateRequest $params): CustomerIdentity
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return CustomerIdentity::fromArray($json);
    }

    public function retrieve(string $id): CustomerIdentity
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return CustomerIdentity::fromArray($json);
    }
}
