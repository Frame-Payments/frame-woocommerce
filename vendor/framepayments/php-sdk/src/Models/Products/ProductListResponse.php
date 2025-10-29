<?php

declare(strict_types=1);

namespace Frame\Models\Products;

final class ProductListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $meta,
        public readonly array $products
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            products: isset($p['data']) && is_array($p['data']) ? array_map(fn (array $pm) => Product::fromArray($pm), $p['data']) : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'data' => $this->products,
        ];
    }
}
