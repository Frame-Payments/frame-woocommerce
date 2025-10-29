<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

final class DeletedResponse implements \JsonSerializable
{
    public function __construct(
        public readonly string $object,
        public readonly bool $deleted
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            object: $p['object'],
            deleted: (bool)$p['deleted'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'object' => $this->object,
            'deleted' => $this->deleted,
        ];
    }
}
