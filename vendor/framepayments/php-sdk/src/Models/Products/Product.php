<?php

declare(strict_types=1);

namespace Frame\Models\Products;

final class Product implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $image,
        public readonly string $description,
        public readonly int $defaultPrice,
        public readonly bool $shippable,
        public readonly bool $active,
        public readonly ?string $url,
        public readonly array $metadata,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            id: $p['id'],
            name: $p['name'],
            image: $p['image'] ?? null,
            description: $p['description'],
            defaultPrice: $p['default_price'],
            shippable: (bool)$p['shippable'],
            active: (bool)$p['active'],
            url: $p['url'] ?? null,
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : [],
            livemode: (bool)$p['livemode'],
            created: (int)$p['created'],
            updated: (int)$p['updated'],
            object: $p['object']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image,
            'description' => $this->description,
            'default_price' => $this->defaultPrice,
            'shippable' => $this->shippable,
            'active' => $this->active,
            'url' => $this->url,
            'metadata' => $this->metadata,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
