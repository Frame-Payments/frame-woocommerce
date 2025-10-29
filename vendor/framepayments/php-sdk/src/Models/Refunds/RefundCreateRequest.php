<?php

declare(strict_types=1);

namespace Frame\Models\Refunds;

final class RefundCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly int $amount,
        public readonly string $chargeIntent,
        public readonly RefundReason $reason,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'amount' => $this->amount,
            'charge_intent' => $this->chargeIntent,
            'reason' => $this->reason->value,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
