<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

final class SubscriptionUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $description = null,
        public readonly ?string $defaultPaymentMethod = null,
        public readonly ?string $product = null,
        public readonly ?string $updateInterval = null,
        public readonly ?array $metadata = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'description' => $this->description,
            'default_payment_method' => $this->defaultPaymentMethod,
            'product' => $this->product,
            'update_interval' => $this->updateInterval,
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
