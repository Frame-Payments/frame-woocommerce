<?php

declare(strict_types=1);

namespace Frame\Models\Disputes;

final class DisputeEvidence implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $accessActivityLog,
        public readonly ?string $billingAddress,
        public readonly ?string $cancellationPolicy,
        public readonly ?string $cancellationPolicyDisclosure,
        public readonly ?string $cancellationRebuttal,
        public readonly ?string $customerEmailAddress,
        public readonly ?string $customerName,
        public readonly ?string $customerPurchaseIp,
        public readonly ?string $duplicateChargeExplanation,
        public readonly ?string $duplicateChargeId,
        public readonly ?string $productDescription,
        public readonly ?string $refundPolicyDisclosure,
        public readonly ?string $shippingTrackingNumber,
        public readonly ?string $uncategorizedText,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            accessActivityLog: $p['access_activity_log'] ?? null,
            billingAddress: $p['billing_address'] ?? null,
            cancellationPolicy: $p['cancellation_policy'] ?? null,
            cancellationPolicyDisclosure: $p['cancellation_policy_disclosure'] ?? null,
            cancellationRebuttal: $p['cancellation_rebuttal'] ?? null,
            customerEmailAddress: $p['customer_email_address'] ?? null,
            customerName: $p['customer_name'] ?? null,
            customerPurchaseIp: $p['customer_purchase_ip'] ?? null,
            duplicateChargeExplanation:$p['duplicate_charge_explanation'] ?? null,
            duplicateChargeId: $p['duplicate_charge_id'] ?? null,
            productDescription: $p['product_description'] ?? null,
            refundPolicyDisclosure: $p['refund_policy_disclosure'] ?? null,
            shippingTrackingNumber: $p['shipping_tracking_number'] ?? null,
            uncategorizedText: $p['uncategorized_text'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'access_activity_log' => $this->accessActivityLog,
            'billing_address' => $this->billingAddress,
            'cancellation_policy' => $this->cancellationPolicy,
            'cancellation_policy_disclosure' => $this->cancellationPolicyDisclosure,
            'cancellation_rebuttal' => $this->cancellationRebuttal,
            'customer_email_address' => $this->customerEmailAddress,
            'customer_name' => $this->customerName,
            'customer_purchase_ip' => $this->customerPurchaseIp,
            'duplicate_charge_explanation' => $this->duplicateChargeExplanation,
            'duplicate_charge_id' => $this->duplicateChargeId,
            'product_description' => $this->productDescription,
            'refund_policy_disclosure' => $this->refundPolicyDisclosure,
            'shipping_tracking_number' => $this->shippingTrackingNumber,
            'uncategorized_text' => $this->uncategorizedText,
        ];
    }
}
