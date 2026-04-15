<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

use Frame\Models\SubscriptionPhases\SubscriptionPhase;

final class Subscription implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $description,
        public readonly int $currentPeriodStart,
        public readonly int $currentPeriodEnd,
        public readonly bool $livemode,
        public readonly ?SubscriptionPlan $plan,
        public readonly string $currency,
        public readonly ?SubscriptionStatus $status,
        public readonly string $customer,
        public readonly ?string $account,
        public readonly ?string $defaultPaymentMethod,
        public readonly ?int $quantity,
        public readonly array $phases,
        public readonly bool $hasPhases,
        public readonly ?SubscriptionPhase $currentPhase,
        public readonly ?int $effectiveAmount,
        public readonly ?string $effectiveInterval,
        public readonly ?int $effectiveIntervalCount,
        public readonly ?string $latestChargeIntent,
        public readonly array $metadata,
        public readonly int $startDate,
        public readonly int $created,
        public readonly string $object,
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = SubscriptionStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected 'SubscriptionStatus': " . $p['status']);
            }
        }

        return new self(
            id: $p['id'],
            description: $p['description'] ?? null,
            currentPeriodStart: (int)$p['current_period_start'],
            currentPeriodEnd: (int)$p['current_period_end'],
            livemode: (bool)$p['livemode'],
            plan: isset($p['plan']) && is_array($p['plan']) ? SubscriptionPlan::fromArray($p['plan']) : null,
            currency: $p['currency'],
            status: $status,
            customer: $p['customer'],
            account: $p['account'] ?? null,
            defaultPaymentMethod: $p['default_payment_method'] ?? null,
            quantity: isset($p['quantity']) ? (int)$p['quantity'] : null,
            phases: isset($p['phases']) && is_array($p['phases'])
                ? array_map(fn (array $phase) => SubscriptionPhase::fromArray($phase), $p['phases'])
                : [],
            hasPhases: (bool)($p['has_phases'] ?? false),
            currentPhase: isset($p['current_phase']) && is_array($p['current_phase'])
                ? SubscriptionPhase::fromArray($p['current_phase'])
                : null,
            effectiveAmount: isset($p['effective_amount']) ? (int)$p['effective_amount'] : null,
            effectiveInterval: $p['effective_interval'] ?? null,
            effectiveIntervalCount: isset($p['effective_interval_count']) ? (int)$p['effective_interval_count'] : null,
            latestChargeIntent: $p['latest_charge_intent'] ?? null,
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : [],
            startDate: (int)$p['start_date'],
            created: (int)$p['created'],
            object: $p['object'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'current_period_start' => $this->currentPeriodStart,
            'current_period_end' => $this->currentPeriodEnd,
            'livemode' => $this->livemode,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'customer' => $this->customer,
            'account' => $this->account,
            'default_payment_method' => $this->defaultPaymentMethod,
            'quantity' => $this->quantity,
            'phases' => $this->phases,
            'has_phases' => $this->hasPhases,
            'current_phase' => $this->currentPhase,
            'effective_amount' => $this->effectiveAmount,
            'effective_interval' => $this->effectiveInterval,
            'effective_interval_count' => $this->effectiveIntervalCount,
            'latest_charge_intent' => $this->latestChargeIntent,
            'metadata' => $this->metadata,
            'start_date' => $this->startDate,
            'created' => $this->created,
            'object' => $this->object,
            'plan' => $this->plan,
        ];
    }
}
