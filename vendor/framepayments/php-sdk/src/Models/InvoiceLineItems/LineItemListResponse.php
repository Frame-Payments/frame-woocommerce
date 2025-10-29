<?php

declare(strict_types=1);

namespace Frame\Models\InvoiceLineItems;

final class LineItemListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $lineItems
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            lineItems: isset($p['data']) && is_array($p['data']) ? array_map(fn (array $pm) => InvoiceLineItem::fromArray($pm), $p['data']) : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->lineItems,
        ];
    }
}
