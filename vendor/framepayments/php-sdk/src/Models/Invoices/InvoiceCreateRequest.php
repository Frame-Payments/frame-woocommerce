<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

final class InvoiceCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $customer,
        public readonly InvoiceCollectionMethod $collectionMethod,
        public readonly ?int $netTerms = null,
        public readonly ?string $number = null,
        public readonly ?string $description = null,
        public readonly ?string $memo = null,
        /** @var array<string,string>|null */
        public readonly ?array $metadata = null,
        public readonly ?InvoiceLineItems $lineItems = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'customer' => $this->customer,
            'collection_method' => $this->collectionMethod->value,
            'net_terms' => $this->netTerms,
            'number' => $this->number,
            'description' => $this->description,
            'memo' => $this->memo,
            'metadata' => $this->metadata,
            'line_items' => $this->lineItems,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
