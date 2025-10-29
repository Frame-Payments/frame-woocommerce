<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

final class SubscriptionPlan implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $interval,
        public readonly int $intervalCount,
        public readonly string $product,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $object,
        public readonly bool $active,
        public readonly int $created,
        public readonly bool $livemode
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            id: $p['id'],
            interval: $p['interval'],
            intervalCount: (int)$p['interval_count'],
            product: $p['product'],
            amount: (int)$p['amount'],
            livemode: (bool)$p['livemode'],
            currency: $p['currency'],
            created: (int)$p['created'],
            object: $p['object'],
            active: (bool)$p['active']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'interval' => $this->interval,
            'interval_count' => $this->intervalCount,
            'product' => $this->product,
            'amount' => $this->amount,
            'active' => $this->active,
            'livemode' => $this->livemode,
            'currency' => $this->currency,
            'created' => $this->created,
            'object' => $this->object,
        ];
    }
}
