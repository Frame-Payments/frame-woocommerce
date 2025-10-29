<?php

declare(strict_types=1);

namespace Frame\Models\Products;

final class ProductUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?int $defaultPrice = null,
        public readonly ?bool $shippable = null,
        public readonly ?string $url = null,
        /** @var array<string,string>|null */
        public readonly ?array $metadata = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'shippable' => $this->shippable,
            'url' => $this->url,
            'default_price' => $this->defaultPrice,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
