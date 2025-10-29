<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

final class SubscriptionCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $product,
        public readonly string $currency,
        public readonly string $customer,
        public readonly string $defaultPaymentMethod,
        public readonly ?string $description = null,
        public readonly ?array $metadata = null
    ) {
        if (strtolower($this->currency) !== 'usd') {
            throw new \InvalidArgumentException('currency must be USD');
        }
    }

    public function toArray(): array
    {
        $payload = [
            'customer' => $this->customer,
            'product' => $this->product,
            'description' => $this->description,
            'currency' => $this->currency,
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
