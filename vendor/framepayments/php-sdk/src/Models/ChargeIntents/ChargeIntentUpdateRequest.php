<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

final class ChargeIntentUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?int $amount,
        public readonly ?string $customer = null,
        public readonly ?string $description = null,
        public readonly ?string $paymentMethod = null,
        /** @var array<string,string>|null */
        public readonly ?array $metadata = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'amount' => $this->amount,
            'description' => $this->description,
            'customer' => $this->customer,
            'payment_method' => $this->paymentMethod,
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
