<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

final class InvoiceLineItems implements \JsonSerializable
{
    public function __construct(
        public readonly string $product,
        public readonly int $quantity
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            product: $p['product'],
            quantity: (int)$p['quantity']
        );
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
