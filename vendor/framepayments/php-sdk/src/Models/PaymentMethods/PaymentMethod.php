<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

use Frame\Models\Customers\Address;

final class PaymentMethod implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $customer,
        public readonly ?string $accountId,
        public readonly ?Address $billing,
        public readonly ?PaymentMethodType $type,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly ?int $updated,
        public readonly string $object,
        public readonly ?PaymentMethodStatus $status,
        public readonly ?PaymentCard $card,
        public readonly ?PaymentAch $ach,
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = PaymentMethodStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected PaymentMethodStatus: " . $p['status']);
            }
        }

        $type = null;
        if (isset($p['type'])) {
            $type = PaymentMethodType::tryFrom($p['type']);
            if ($type === null) {
                error_log("Unexpected PaymentMethodType: " . $p['type']);
            }
        }

        return new self(
            id: $p['id'],
            customer: $p['customer_id'] ?? $p['customer'] ?? null,
            accountId: $p['account_id'] ?? null,
            billing: isset($p['billing']) && is_array($p['billing']) ? Address::fromArray($p['billing']) : null,
            type: $type,
            created: (int)$p['created'],
            updated: isset($p['updated']) ? (int)$p['updated'] : null,
            livemode: (bool)$p['livemode'],
            object: $p['object'],
            status: $status,
            card: isset($p['card']) && is_array($p['card']) ? PaymentCard::fromArray($p['card']) : null,
            ach: isset($p['ach']) && is_array($p['ach']) ? PaymentAch::fromArray($p['ach']) : null,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer,
            'account_id' => $this->accountId,
            'billing' => $this->billing,
            'type' => $this->type?->value,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
            'status' => $this->status?->value,
            'card' => $this->card,
            'ach' => $this->ach,
        ];
    }
}
