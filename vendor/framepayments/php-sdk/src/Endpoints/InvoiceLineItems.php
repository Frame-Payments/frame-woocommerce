<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\InvoiceLineItems\InvoiceLineItem;
use Frame\Models\InvoiceLineItems\LineItemCreateRequest;
use Frame\Models\InvoiceLineItems\LineItemListResponse;
use Frame\Models\InvoiceLineItems\LineItemUpdateRequest;
use Frame\Models\Invoices\DeletedResponse;

final class InvoiceLineItems
{
    private const BASE_PATH = '/v1/invoices';

    public function create(string $invoiceId, LineItemCreateRequest $params): InvoiceLineItem
    {
        $json = Client::post(self::BASE_PATH . "/{$invoiceId}/line_items", $params->toArray());

        return InvoiceLineItem::fromArray($json);
    }

    public function update(string $invoiceId, string $itemId, LineItemUpdateRequest $params): InvoiceLineItem
    {
        $json = Client::update(self::BASE_PATH . "/{$invoiceId}/line_items/{$itemId}", $params->toArray());

        return InvoiceLineItem::fromArray($json);
    }

    public function retrieve(string $invoiceId, string $itemId): InvoiceLineItem
    {
        $json = Client::get(self::BASE_PATH . "/{$invoiceId}/line_items/{$itemId}");

        return InvoiceLineItem::fromArray($json);
    }

    public function list(string $invoiceId, int $perPage = 10, int $page = 1): LineItemListResponse
    {
        $json = Client::get(self::BASE_PATH . "/{$invoiceId}/line_items", ['per_page' => $perPage, 'page' => $page]);

        return LineItemListResponse::fromArray($json);
    }

    public function delete(string $invoiceId, string $itemId): DeletedResponse
    {
        $json = Client::delete(self::BASE_PATH . "/{$invoiceId}/line_items/{$itemId}");

        return DeletedResponse::fromArray($json);
    }
}
