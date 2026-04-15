<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

final class InvoiceCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $customer,
        public readonly InvoiceCollectionMethod $collectionMethod,
        public readonly ?string $account = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?int $netTerms = null,
        public readonly ?string $number = null,
        public readonly ?string $description = null,
        public readonly ?string $memo = null,
        /** @var string[]|null */
        public readonly ?array $promotionCodes = null,
        /** @var array<string,string>|null */
        public readonly ?array $metadata = null,
        public readonly ?InvoiceLineItems $lineItems = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'customer' => $this->customer,
            'account' => $this->account,
            'collection_method' => $this->collectionMethod->value,
            'payment_method' => $this->paymentMethod,
            'net_terms' => $this->netTerms,
            'number' => $this->number,
            'description' => $this->description,
            'memo' => $this->memo,
            'promotion_codes' => $this->promotionCodes,
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
