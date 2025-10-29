<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

use Frame\Models\Customers\Address;
use Frame\Models\Customers\Customer;
use Frame\Models\PaymentMethods\PaymentMethod;

final class ChargeIntent implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $currency,
        public readonly ?Customer $customer,
        public readonly ?PaymentMethod $paymentMethod,
        public readonly ?Address $shipping,
        public readonly ?ChargeIntentStatus $status,
        public readonly ?string $clientSecret,
        public readonly ?string $description,
        public readonly int $amount,
        public readonly int $created,
        public readonly int $updated,
        public readonly bool $livemode,
        public readonly string $object,
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = ChargeIntentStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected ChargeIntentStatus: " . $p['status']);
            }
        }

        return new self(
            id: $p['id'],
            currency: $p['currency'],
            customer: isset($p['customer']) && is_array($p['customer']) ? Customer::fromArray($p['customer']) : null,
            paymentMethod: isset($p['payment_method']) && is_array($p['payment_method']) ? PaymentMethod::fromArray($p['payment_method']) : null,
            shipping: isset($p['shipping']) && is_array($p['shipping']) ? Address::fromArray($p['shipping']) : null,
            clientSecret: $p['client_secret'] ?? null,
            status: $status,
            description: $p['description'] ?? null,
            amount: (int)$p['amount'],
            created: (int)$p['created'],
            updated: (int)$p['updated'],
            livemode: (bool)$p['livemode'],
            object: $p['object']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'customer' => $this->customer,
            'payment_method' => $this->paymentMethod,
            'shipping' => $this->shipping,
            'client_secret' => $this->clientSecret,
            'status' => $this->status->value,
            'description' => $this->description,
            'amount' => $this->amount,
            'created' => $this->created,
            'updated' => $this->updated,
            'livemode' => $this->livemode,
            'object' => $this->object,
        ];
    }
}
