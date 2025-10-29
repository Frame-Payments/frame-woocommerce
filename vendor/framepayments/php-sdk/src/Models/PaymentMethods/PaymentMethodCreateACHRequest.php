<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

use Frame\Models\Customers\Address;

final class PaymentMethodCreateACHRequest implements \JsonSerializable
{
    public function __construct(
        public readonly PaymentMethodType $type,
        public readonly ?string $customer,
        public readonly string $accountType,
        public readonly string $accountNumber,
        public readonly string $routingNumber,
        public readonly ?Address $billing = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'type' => $this->type->value,
            'account_type' => $this->accountType,
            'account_number' => $this->accountNumber,
            'routing_number' => $this->routingNumber,
            'customer' => $this->customer,
            'billing' => $this->billing?->toArray(),
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
