<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

final class InvoiceListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $meta,
        public readonly array $invoices
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            invoices: isset($p['data']) && is_array($p['data']) ? array_map(fn (array $pm) => Invoice::fromArray($pm), $p['data']) : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'data' => $this->invoices,
        ];
    }
}
