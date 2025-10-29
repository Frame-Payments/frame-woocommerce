<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

use Frame\Models\PaymentMethods\PaymentMethodData;

final class ChargeIntentCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $description = null,
        public readonly ?string $customer = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?bool $confirm = null,
        public readonly ?string $receiptEmail = null,
        public readonly ?PaymentMethodData $paymentMethodData = null,
        public readonly ?ChargeIntentCustomerData $customerData = null,
        /** @var array<string,string>|null */
        public readonly ?array $metadata = null,
        public readonly ?AuthorizationMode $authorizationMode = null,
    ) {
        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('amount must be > 0');
        }
    }

    public function toArray(): array
    {
        $payload = [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'customer' => $this->customer,
            'payment_method' => $this->paymentMethod,
            'confirm' => $this->confirm,
            'receipt_email' => $this->receiptEmail,
            'payment_method_data' => $this->paymentMethodData?->toArray(),
            'customer_data' => $this->customerData?->toArray(),
            'metadata' => $this->metadata,
            'authorization_mode' => $this->authorizationMode?->value,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
