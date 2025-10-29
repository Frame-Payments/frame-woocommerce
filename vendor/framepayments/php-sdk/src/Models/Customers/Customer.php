<?php

declare(strict_types=1);

namespace Frame\Models\Customers;

use Frame\Models\PaymentMethods\PaymentMethod;

final class Customer implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?Address $billingAddress,
        public readonly ?Address $shippingAddress,
        public readonly string $name,
        public readonly ?CustomerStatus $status,
        public readonly array $paymentMethods,
        public readonly ?string $description,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $dateOfBirth,
        public readonly ?array $metadata,
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
            $status = CustomerStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected 'CustomerStatus': " . $p['status']);
            }
        }

        return new self(
            id: $p['id'],
            billingAddress: isset($p['billing_address']) && is_array($p['billing_address']) ? Address::fromArray($p['billing_address']) : null,
            shippingAddress: isset($p['shipping_address']) && is_array($p['shipping_address']) ? Address::fromArray($p['shipping_address']) : null,
            name: $p['name'],
            status: $status,
            paymentMethods: isset($p['payment_methods']) && is_array($p['payment_methods']) ? array_map(fn ($pm) => PaymentMethod::fromArray($pm), $p['payment_methods']) : [],
            description: $p['description'] ?? null,
            email: $p['email'],
            phone: $p['phone'] ?? null,
            dateOfBirth: $p['date_of_birth'] ?? null,
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : [],
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
            'billing_address' => $this->billingAddress,
            'shipping_address' => $this->shippingAddress,
            'name' => $this->name,
            'payment_methods' => $this->paymentMethods,
            'status' => $this->status->value,
            'description' => $this->description,
            'email' => $this->email,
            'phone' => $this->phone,
            'date_of_birth' => $this->dateOfBirth,
            'metadata' => $this->metadata,
            'livemode' => $this->livemode,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
