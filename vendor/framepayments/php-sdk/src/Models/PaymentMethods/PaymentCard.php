<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

final class PaymentCard implements \JsonSerializable
{
    public function __construct(
        public readonly string $brand,
        public readonly string $expMonth,
        public readonly string $expYear,
        public readonly ?string $currency,
        public readonly string $lastFour,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            brand: $p['brand'],
            expMonth: $p['exp_month'],
            expYear: $p['exp_year'],
            currency: $p['currency'] ?? null,
            lastFour: $p['last_four']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'brand' => $this->brand,
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
            'currency' => $this->currency,
            'last_four' => $this->lastFour,
        ];
    }
}
