<?php

declare(strict_types=1);

namespace Frame\Models\Accounts;

final class Account implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?AccountType $type,
        public readonly ?AccountStatus $status,
        public readonly ?string $externalId,
        public readonly ?array $metadata,
        public readonly ?string $payoutPaymentMethodId,
        public readonly ?array $termsOfService,
        public readonly ?array $profile,
        public readonly array $capabilities,
        public readonly array $steps,
        public readonly int $created,
        public readonly int $updated,
        public readonly bool $livemode,
        public readonly string $object,
    ) {
    }

    public static function fromArray(array $p): self
    {
        $type = null;
        if (isset($p['type'])) {
            $type = AccountType::tryFrom($p['type']);
            if ($type === null) {
                error_log("Unexpected AccountType: " . $p['type']);
            }
        }

        $status = null;
        if (isset($p['status'])) {
            $status = AccountStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected AccountStatus: " . $p['status']);
            }
        }

        return new self(
            id: $p['id'],
            type: $type,
            status: $status,
            externalId: $p['external_id'] ?? null,
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : null,
            payoutPaymentMethodId: $p['payout_payment_method_id'] ?? null,
            termsOfService: isset($p['terms_of_service']) && is_array($p['terms_of_service']) ? $p['terms_of_service'] : null,
            profile: isset($p['profile']) && is_array($p['profile']) ? $p['profile'] : null,
            capabilities: isset($p['capabilities']) && is_array($p['capabilities']) ? $p['capabilities'] : [],
            steps: isset($p['steps']) && is_array($p['steps']) ? $p['steps'] : [],
            created: (int)$p['created'],
            updated: (int)$p['updated'],
            livemode: (bool)$p['livemode'],
            object: $p['object'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'external_id' => $this->externalId,
            'metadata' => $this->metadata,
            'payout_payment_method_id' => $this->payoutPaymentMethodId,
            'terms_of_service' => $this->termsOfService,
            'profile' => $this->profile,
            'capabilities' => $this->capabilities,
            'steps' => $this->steps,
            'created' => $this->created,
            'updated' => $this->updated,
            'livemode' => $this->livemode,
            'object' => $this->object,
        ];
    }
}
