<?php

declare(strict_types=1);

namespace Frame\Models\Customers;

final class CustomerSearchRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?int $createdBefore = null,
        public readonly ?int $createdAfter = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'created_before' => $this->createdBefore,
            'created_after' => $this->createdAfter,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
