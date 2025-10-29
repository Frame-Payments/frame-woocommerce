<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

final class Subscription implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly int $currentPeriodStart,
        public readonly int $currentPeriodEnd,
        public readonly bool $livemode,
        public readonly ?SubscriptionPlan $plan,
        public readonly string $currency,
        public readonly ?SubscriptionStatus $status,
        public readonly string $customer,
        public readonly string $defaultPaymentMethod,
        public readonly array $metadata,
        public readonly int $startDate,
        public readonly int $created,
        public readonly string $object
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
            description: $p['description'],
            currentPeriodStart: (int)$p['current_period_start'],
            currentPeriodEnd: (int)$p['current_period_end'],
            livemode: (bool)$p['livemode'],
            plan: isset($p['plan']) && is_array($p['plan']) ? SubscriptionPlan::fromArray($p['plan']) : null,
            currency: $p['currency'],
            status: $status,
            customer: $p['customer'],
            defaultPaymentMethod: $p['default_payment_method'],
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : [],
            startDate: (int)$p['start_date'],
            created: (int)$p['created'],
            object: $p['object']
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
            'default_payment_method' => $this->defaultPaymentMethod,
            'metadata' => $this->metadata,
            'start_date' => $this->startDate,
            'created' => $this->created,
            'object' => $this->object,
            'plan' => $this->plan,
        ];
    }
}
