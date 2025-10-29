<?php

declare(strict_types=1);

namespace Frame\Models\Customers;

final class CustomerUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $ssn = null,
        public readonly ?string $dateOfBirth = null,
        /** @var array<string,string>|null */
        public readonly ?array $metadata = null,
        public readonly ?Address $billingAddress = null,
        public readonly ?Address $shippingAddress = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'email' => $this->email,
            'description' => $this->description,
            'phone' => $this->phone,
            'ssn' => $this->ssn,
            'date_of_birth' => $this->dateOfBirth,
            'metadata' => $this->metadata,
            'billing_address' => $this->billingAddress?->toArray(),
            'shipping_address' => $this->shippingAddress?->toArray(),
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
