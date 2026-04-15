<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

final class PaymentAch implements \JsonSerializable
{
    public function __construct(
        public readonly string $accountType,
        public readonly string $bankName,
        public readonly string $routingNumber,
        public readonly string $lastFour,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            accountType: $p['account_type'],
            bankName: $p['bank_name'],
            routingNumber: $p['routing_number'],
            lastFour: $p['last_four'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'account_type' => $this->accountType,
            'bank_name' => $this->bankName,
            'routing_number' => $this->routingNumber,
            'last_four' => $this->lastFour,
        ];
    }
}
