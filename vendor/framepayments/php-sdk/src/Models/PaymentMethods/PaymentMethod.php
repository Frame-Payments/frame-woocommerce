<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

use Frame\Models\Customers\Address;

final class PaymentMethod implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $customer,
        public readonly Address $billing,
        public readonly ?PaymentMethodType $type,
        public readonly bool $livemode,
        public readonly int $created,
        public readonly ?int $updated,
        public readonly string $object,
        public readonly ?PaymentMethodStatus $status,
        public readonly ?PaymentCard $card,
        // public readonly ?string $ach
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
            customer: $p['customer'] ?? null,
            billing: Address::fromArray($p['billing']),
            type: $type,
            created: (int)$p['created'],
            updated: (int)$p['updated'] ?? null,
            livemode: (bool)$p['livemode'],
            object: $p['object'],
            status: $status,
            card: isset($p['card']) && is_array($p['card']) ? PaymentCard::fromArray($p['card']) : null
            // ach: $p['ach'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'customer' => $this->customer,
            'billing' => $this->billing,
            'type' => $this->type->value,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
            'status' => $this->status->value,
            'card' => $this->card,
            // 'ach' => $this->ach,
        ];
    }
}
