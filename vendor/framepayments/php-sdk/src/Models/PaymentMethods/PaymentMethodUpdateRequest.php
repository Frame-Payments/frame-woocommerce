<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

use Frame\Models\Customers\Address;

final class PaymentMethodUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $expMonth,
        public readonly ?string $expYear,
        public readonly ?Address $billing = null
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
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
