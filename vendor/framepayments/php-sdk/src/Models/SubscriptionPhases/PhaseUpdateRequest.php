<?php

declare(strict_types=1);

namespace Frame\Models\SubscriptionPhases;

final class PhaseUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?int $ordinal = null,
        public readonly ?string $name = null,
        public readonly ?PhasePricingType $pricingType = null,
        public readonly ?int $amountCents = null,
        public readonly ?float $discountPercentage = null,
        public readonly ?int $periodCount = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'ordinal' => $this->ordinal,
            'name' => $this->name,
            'pricing_type' => $this->pricingType?->value,
            'amount_cents' => $this->amountCents,
            'discount_percentage' => $this->discountPercentage,
            'period_count' => $this->periodCount,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
