<?php

declare(strict_types=1);

namespace Frame\Models\Products;

final class ProductCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $defaultPrice,
        public readonly ProductPurchaseType $purchaseType,
        public readonly ?ProductRecurringInterval $recurringInterval = null,
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
            'default_price' => $this->defaultPrice,
            'purchase_type' => $this->purchaseType->value,
            'recurring_interval' => $this->recurringInterval?->value,
            'shippable' => $this->shippable,
            'url' => $this->url,
            'metadata' => $this->metadata,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
