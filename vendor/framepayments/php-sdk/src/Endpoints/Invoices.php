<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Invoices\DeletedResponse;
use Frame\Models\Invoices\Invoice;
use Frame\Models\Invoices\InvoiceCreateRequest;
use Frame\Models\Invoices\InvoiceListResponse;
use Frame\Models\Invoices\InvoiceStatus;
use Frame\Models\Invoices\InvoiceUpdateRequest;

final class Invoices
{
    private const BASE_PATH = '/v1/invoices';

    public function create(InvoiceCreateRequest $params): Invoice
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return Invoice::fromArray($json);
    }

    public function update(string $id, InvoiceUpdateRequest $params): Invoice
    {
        $json = Client::update(self::BASE_PATH . "/{$id}", $params->toArray());

        return Invoice::fromArray($json);
    }

    public function retrieve(string $id): Invoice
    {
        $json = Client::get(self::BASE_PATH . "/{$id}");

        return Invoice::fromArray($json);
    }

    public function delete(string $id): DeletedResponse
    {
        $json = Client::delete(self::BASE_PATH . "/{$id}");

        return DeletedResponse::fromArray($json);
    }

    public function list(int $perPage = 10, int $page = 1, ?string $customer = null, ?InvoiceStatus $status = null): InvoiceListResponse
    {
        $json = Client::get(self::BASE_PATH, ['per_page' => $perPage, 'page' => $page, 'customer' => $customer, 'status' => $status?->value]);

        return InvoiceListResponse::fromArray($json);
    }

    public function issue(string $id): Invoice
    {
        $json = Client::post(self::BASE_PATH . "/{$id}/issue");

        return Invoice::fromArray($json);
    }
}
