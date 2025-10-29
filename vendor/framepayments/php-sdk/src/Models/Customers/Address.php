<?php

declare(strict_types=1);

namespace Frame\Models\Customers;

final class Address implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly ?string $state,
        public readonly ?string $postalCode,
        public readonly ?string $line1,
        public readonly ?string $line2
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            city: $p['city'] ?? null,
            country: $p['country'] ?? null,
            state: $p['state'] ?? null,
            postalCode: $p['postal_code'] ?? null,
            line1: $p['line_1'] ?? null,
            line2: $p['line_2'] ?? null
        );
    }

    public function toArray(): array
    {
        $payload = [
            'city' => $this->city,
            'country' => $this->country,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'line_1' => $this->line1,
            'line_2' => $this->line2,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
