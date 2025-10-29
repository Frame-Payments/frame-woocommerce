<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

final class PaymentMethodListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $meta,
        public readonly array $methods
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            methods: isset($p['data']) && is_array($p['data']) ? array_map(fn (array $pm) => PaymentMethod::fromArray($pm), $p['data']) : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'data' => $this->methods,
        ];
    }
}
