<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

final class ChargeIntentCustomerData implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            name: $p['name'],
            email: $p['email'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
