<?php

declare(strict_types=1);

namespace Frame\Models\SubscriptionPhases;

final class SubscriptionPhase implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly int $ordinal,
        public readonly ?string $name,
        public readonly ?PhasePricingType $pricingType,
        public readonly ?int $amount,
        public readonly string $currency,
        public readonly ?float $discountPercentage,
        public readonly ?int $periodCount,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object
    ) {
    }

    public static function fromArray(array $p): self
    {
        $pricingType = null;
        if (isset($p['pricing_type'])) {
            $pricingType = PhasePricingType::tryFrom($p['pricing_type']);
            if ($pricingType === null) {
                error_log("Unexpected 'PhasePricingType': " . $p['pricing_type']);
            }
        }

        return new self(
            id: $p['id'],
            ordinal: (int)$p['ordinal'],
            name: isset($p['name']) ? $p['name'] : null,
            pricingType: $pricingType,
            amount: isset($p['amount']) ? (int)$p['amount'] : null,
            currency: $p['currency'],
            discountPercentage: isset($p['discount_percentage']) ? (float)$p['discount_percentage'] : null,
            periodCount: isset($p['period_count']) ? (int)$p['period_count'] : null,
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
            'ordinal' => $this->ordinal,
            'name' => $this->name,
            'pricing_type' => $this->pricingType?->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'discount_percentage' => $this->discountPercentage,
            'period_count' => $this->periodCount,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
