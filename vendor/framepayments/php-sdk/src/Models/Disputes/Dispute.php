<?php

declare(strict_types=1);

namespace Frame\Models\Disputes;

final class Dispute implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly int $amount,
        public readonly ?string $charge,
        public readonly string $currency,
        public readonly DisputeEvidence $evidence,
        public readonly ?string $chargeIntent,
        public readonly ?DisputeReason $reason,
        public readonly ?DisputeStatus $status,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = DisputeStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected 'DisputeStatus': " . $p['status']);
            }
        }

        $reason = null;
        if (isset($p['reason'])) {
            $reason = DisputeReason::tryFrom($p['reason']);
            if ($reason === null) {
                error_log("Unexpected 'DisputeReason': " . $p['reason']);
            }
        }

        return new self(
            id: $p['id'],
            amount: (int)$p['amount'],
            charge: isset($p['charge']) ? $p['charge'] : null,
            currency: $p['currency'],
            evidence: isset($p['evidence']) && is_array($p['evidence']) ? DisputeEvidence::fromArray($p['evidence']) : [],
            chargeIntent: isset($p['charge_intent']) ? $p['charge_intent'] : null,
            reason: $reason,
            status: $status,
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
            'amount' => $this->amount,
            'charge' => $this->charge,
            'currency' => $this->currency,
            'evidence' => $this->evidence,
            'charge_intent' => $this->chargeIntent,
            'reason' => $this->reason->value,
            'status' => $this->status->value,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
