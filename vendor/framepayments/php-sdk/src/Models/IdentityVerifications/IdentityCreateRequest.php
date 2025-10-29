<?php

declare(strict_types=1);

namespace Frame\Models\IdentityVerifications;

use Frame\Models\Customers\Address;

final class IdentityCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $dateOfBirth,
        public readonly string $email,
        public readonly string $phoneNumber,
        public readonly string $ssn,
        public readonly Address $address
    ) {
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'date_of_birth' => $this->dateOfBirth,
            'email' => $this->email,
            'phone_number' => $this->phoneNumber,
            'ssn' => $this->ssn,
            'address' => $this->address,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
