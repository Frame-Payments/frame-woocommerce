<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

use Frame\Models\Customers\Customer;

final class Invoice implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?Customer $customer,
        public readonly int $total,
        public readonly string $currency,
        public readonly ?InvoiceStatus $status,
        public readonly ?InvoiceCollectionMethod $collectionMethod,
        public readonly int $netTerms,
        public readonly string $invoiceNumber,
        public readonly string $description,
        public readonly string $memo,
        public readonly array $metadata,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object,
        public readonly array $lineItems
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = InvoiceStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected 'InvoiceStatus': " . $p['status']);
            }
        }

        $collectionMethod = null;
        if (isset($p['collection_method'])) {
            $collectionMethod = InvoiceCollectionMethod::tryFrom($p['collection_method']);
            if ($collectionMethod === null) {
                error_log("Unexpected 'InvoiceCollectionMethod': " . $p['collection_method']);
            }
        }

        return new self(
            id: $p['id'],
            customer: isset($p['customer']) && is_array($p['customer']) ? Customer::fromArray($p['customer']) : null,
            total: (int)$p['total'],
            currency: $p['currency'],
            status: $status,
            collectionMethod: $collectionMethod,
            netTerms: (int)$p['net_terms'],
            invoiceNumber: $p['invoice_number'],
            description: $p['description'],
            memo: $p['memo'],
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : [],
            livemode: (bool)$p['livemode'],
            created: (int)$p['created'],
            updated: (int)$p['updated'],
            object: $p['object'],
            lineItems: isset($p['line_items']) && is_array($p['line_items']) ? array_map(fn ($pm) => InvoiceLineItems::fromArray($pm), $p['line_items']) : []
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'total' => $this->total,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'collection_method' => $this->collectionMethod?->value,
            'net_terms' => $this->netTerms,
            'invoice_number' => $this->invoiceNumber,
            'description' => $this->description,
            'memo' => $this->memo,
            'metadata' => $this->metadata,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
            'line_items' => $this->lineItems,
        ];
    }
}
