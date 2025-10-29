<?php

declare(strict_types=1);

namespace Frame\Models\Refunds;

final class Refund implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $currency,
        public readonly ?RefundStatus $status,
        public readonly int $amount,
        public readonly ?RefundReason $reason,
        public readonly ?string $chargeIntent,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly ?int $updated,
        public readonly string $object
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = RefundStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected RefundStatus: " . $p['status']);
            }
        }

        $reason = null;
        if (isset($p['reason'])) {
            $reason = RefundReason::tryFrom($p['reason']);
            if ($reason === null) {
                error_log("Unexpected RefundReason: " . $p['reason']);
            }
        }

        return new self(
            id: $p['id'],
            currency: $p['currency'],
            status: $status,
            amount: (int)$p['amount'],
            reason: $reason,
            chargeIntent: $p['charge_intent'] ?? null,
            livemode: (bool)$p['livemode'],
            created: (int)$p['created'],
            updated: (int)$p['updated'] ?? null,
            object: $p['object']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'status' => $this->status?->value,
            'amount' => $this->amount,
            'reason' => $this->reason?->value,
            'charge_intent' => $this->chargeIntent,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
