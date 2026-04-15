<?php

declare(strict_types=1);

namespace Frame\Models\Disputes;

use Frame\Models\ChargeIntents\ChargeIntent;

final class Dispute implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly int $amountCents,
        public readonly string $amountCurrency,
        public readonly ?ChargeIntent $chargeIntent,
        public readonly ?array $reason,
        public readonly ?DisputeStatus $status,
        public readonly ?string $displayStatus,
        public readonly ?string $acquirerReferenceNumber,
        public readonly ?string $authorizationCode,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object,
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

        return new self(
            id: $p['id'],
            amountCents: (int)($p['amount_cents'] ?? 0),
            amountCurrency: $p['amount_currency'] ?? '',
            chargeIntent: isset($p['charge_intent']) && is_array($p['charge_intent']) ? ChargeIntent::fromArray($p['charge_intent']) : null,
            reason: isset($p['reason']) && is_array($p['reason']) ? $p['reason'] : null,
            status: $status,
            displayStatus: $p['display_status'] ?? null,
            acquirerReferenceNumber: $p['acquirer_reference_number'] ?? null,
            authorizationCode: $p['authorization_code'] ?? null,
            livemode: (bool)$p['livemode'],
            created: (int)$p['created'],
            updated: (int)$p['updated'],
            object: $p['object'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'amount_cents' => $this->amountCents,
            'amount_currency' => $this->amountCurrency,
            'charge_intent' => $this->chargeIntent,
            'reason' => $this->reason,
            'status' => $this->status?->value,
            'display_status' => $this->displayStatus,
            'acquirer_reference_number' => $this->acquirerReferenceNumber,
            'authorization_code' => $this->authorizationCode,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
