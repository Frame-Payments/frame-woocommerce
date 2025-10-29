<?php

declare(strict_types=1);

namespace Frame\Models\InvoiceLineItems;

final class InvoiceLineItem implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly int $quantity,
        public readonly int $unitAmountCents,
        public readonly string $unitAmountCurrency,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            id: $p['id'],
            object: $p['object'],
            description: $p['description'],
            quantity: (int)$p['quantity'],
            unitAmountCents: (int)$p['unit_amount_cents'],
            unitAmountCurrency: $p['unit_amount_currency'],
            created: (int)$p['created'],
            updated: (int)($p['updated'])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'object' => $this->object,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_amount_cents' => $this->unitAmountCents,
            'unit_amount_currency' => $this->unitAmountCurrency,
            'created' => $this->created,
            'updated' => $this->updated,
        ];
    }
}
