<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

final class SubscriptionUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $description = null,
        public readonly ?string $defaultPaymentMethod = null,
        public readonly ?array $metadata = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'description' => $this->description,
            'default_payment_method' => $this->defaultPaymentMethod,
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
