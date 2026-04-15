<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

use Frame\Models\Accounts\Account;
use Frame\Models\Customers\Address;
use Frame\Models\Customers\Customer;
use Frame\Models\PaymentMethods\PaymentMethod;

final class ChargeIntent implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $currency,
        public readonly ?Customer $customer,
        public readonly ?Account $account,
        public readonly ?PaymentMethod $paymentMethod,
        public readonly ?Address $shipping,
        public readonly ?ChargeIntentStatus $status,
        public readonly ?string $clientSecret,
        public readonly ?string $description,
        public readonly ?string $failureDescription,
        public readonly ?string $authorizationMode,
        public readonly int $amount,
        public readonly int $amountCaptured,
        public readonly int $amountVoided,
        public readonly ?string $subscription,
        public readonly ?string $invoice,
        public readonly ?array $metadata,
        public readonly ?array $latestCharge,
        public readonly ?array $nextAction,
        public readonly ?array $revenueSplit,
        public readonly int $created,
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
            account: isset($p['account']) && is_array($p['account']) ? Account::fromArray($p['account']) : null,
            paymentMethod: isset($p['payment_method']) && is_array($p['payment_method']) ? PaymentMethod::fromArray($p['payment_method']) : null,
            shipping: isset($p['shipping']) && is_array($p['shipping']) ? Address::fromArray($p['shipping']) : null,
            clientSecret: $p['client_secret'] ?? null,
            status: $status,
            description: $p['description'] ?? null,
            failureDescription: $p['failure_description'] ?? null,
            authorizationMode: $p['authorization_mode'] ?? null,
            amount: (int)$p['amount'],
            amountCaptured: (int)($p['amount_captured'] ?? 0),
            amountVoided: (int)($p['amount_voided'] ?? 0),
            subscription: $p['subscription'] ?? null,
            invoice: $p['invoice'] ?? null,
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : null,
            latestCharge: isset($p['latest_charge']) && is_array($p['latest_charge']) ? $p['latest_charge'] : null,
            nextAction: isset($p['next_action']) && is_array($p['next_action']) ? $p['next_action'] : null,
            revenueSplit: isset($p['revenue_split']) && is_array($p['revenue_split']) ? $p['revenue_split'] : null,
            created: (int)$p['created'],
            livemode: (bool)$p['livemode'],
            object: $p['object'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'customer' => $this->customer,
            'account' => $this->account,
            'payment_method' => $this->paymentMethod,
            'shipping' => $this->shipping,
            'client_secret' => $this->clientSecret,
            'status' => $this->status?->value,
            'description' => $this->description,
            'failure_description' => $this->failureDescription,
            'authorization_mode' => $this->authorizationMode,
            'amount' => $this->amount,
            'amount_captured' => $this->amountCaptured,
            'amount_voided' => $this->amountVoided,
            'subscription' => $this->subscription,
            'invoice' => $this->invoice,
            'metadata' => $this->metadata,
            'latest_charge' => $this->latestCharge,
            'next_action' => $this->nextAction,
            'revenue_split' => $this->revenueSplit,
            'created' => $this->created,
            'livemode' => $this->livemode,
            'object' => $this->object,
        ];
    }
}
