<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

use Frame\Models\Customers\Address;

final class PaymentMethodCreateCardRequest implements \JsonSerializable
{
    public function __construct(
        public readonly PaymentMethodType $type,
        public readonly ?string $customer,
        public readonly string $cardNumber,
        public readonly string $expMonth,
        public readonly string $expYear,
        public readonly string $cvc,
        public readonly ?Address $billing = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'type' => $this->type->value,
            'card_number' => $this->cardNumber,
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
            'cvc' => $this->cvc,
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
