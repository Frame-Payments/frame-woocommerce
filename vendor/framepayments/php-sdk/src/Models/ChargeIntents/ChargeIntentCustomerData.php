<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

final class ChargeIntentCustomerData implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone = null,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            name: $p['name'],
            email: $p['email'],
            phone: $p['phone'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
