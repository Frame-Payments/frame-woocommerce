<?php

declare(strict_types=1);

namespace Frame\Models\InvoiceLineItems;

final class LineItemUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $product,
        public readonly ?int $quantity
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'product' => $this->product,
            'quantity' => $this->quantity,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
