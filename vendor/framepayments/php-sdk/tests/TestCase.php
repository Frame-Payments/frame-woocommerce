<?php

namespace Frame\Tests;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Helper method to get a sample deleted responsearray
     */
    protected function getSampleDeletedResponse(): array
    {
        return [
            'object' => 'object',
            'deleted' => true,
        ];
    }

    /**
     * Helper method to get a sample customer data array
     */
    protected function getSampleCustomerData(): array
    {
        return [
            'id' => 'cus_test123',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'status' => 'active',
            'description' => 'Test customer',
            'date_of_birth' => '1990-01-01',
            'livemode' => false,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'customer',
            'billing_address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            'shipping_address' => [
                'line1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            'payment_methods' => [],
            'metadata' => [],
        ];
    }

    /**
     * Helper method to get a sample charge intent data array
     */
    protected function getSampleChargeIntentData(): array
    {
        return [
            'id' => 'ci_test123',
            'currency' => 'usd',
            'amount' => 2000,
            'status' => 'incomplete',
            'description' => 'Test charge intent',
            'client_secret' => 'ci_test123_secret',
            'livemode' => false,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'charge_intent',
            'customer' => null,
            'payment_method' => null,
            'shipping' => null,
        ];
    }

    /**
     * Helper method to get a sample invoice data array
     */
    protected function getSampleInvoiceData(): array
    {
        return [
            'id' => 'inv_123',
            'customer' => 'cust_123',
            'total' => 100,
            'currency' => 'usd',
            'status' => 'outstanding',
            'collection_method' => 'auto_charge',
            'net_terms' => 30,
            'invoice_number' => '11111',
            'description' => 'new invoice',
            'memo' => 'memo',
            'metadata' => [],
            'livemode' => false,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'invoice',
            'line_items' => [],
        ];
    }

    /**
     * Helper method to get a sample invoice line item data array
     */
    protected function getSampleInvoiceLineItemData(): array
    {
        return [
            'id' => 'lineItem_123',
            'description' => 'current invoice line item',
            'quantity' => 0,
            'unit_amount_cents' => 0,
            'unit_amount_currency' => 'usd',
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'invoice_line_item',
        ];
    }

    /**
     * Helper method to get a sample address data array
     */
    protected function getSampleAddressData(): array
    {
        return [
            'city' => 'Los Angeles',
            'country' => 'USA',
            'state' => 'CA',
            'postal_code' => '11111',
            'line_1' => '1 Angel Way',
            'line_2' => null,
        ];
    }

    /**
     * Helper method to get a sample customer identity data array
     */
    protected function getSampleCustomerIdentityData(): array
    {
        return [
            'id' => 'cusIdentity_123',
            'status' => 'incomplete',
            'verification_url' => null,
            'pending' => null,
            'verified' => null,
            'failed' => null,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'identity_verification',
        ];
    }

    /**
     * Helper method to get a sample dispute data array
     */
    protected function getSampleDisputeData(): array
    {
        return [
            'id' => 'dis_test123',
            'amount' => 2000,
            'charge' => 'char_123',
            'currency' => 'usd',
            'evidence' => $this->getSampleDisputeEvidenceData(),
            'charge_intent' => 'char_intent123',
            'reason' => 'fraudulent',
            'status' => 'under_review',
            'livemode' => false,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'dispute',
        ];
    }

    /**
     * Helper method to get a sample dispute evidence data array
     */
    protected function getSampleDisputeEvidenceData(): array
    {
        return [
            'access_activity_log' => null,
            'billing_address' => null,
            'cancellation_policy' => null,
            'cancellation_policy_disclosure' => null,
            'cancellation_rebuttal' => null,
            'customer_email_address' => null,
            'customer_name' => null,
            'customer_purchase_ip' => null,
            'duplicate_charge_explanation' => null,
            'duplicate_charge_id' => null,
            'product_description' => null,
            'refund_policy_disclosure' => null,
            'shipping_tracking_number' => null,
            'uncategorized_text' => null,
        ];
    }

    /**
     * Helper method to get a sample payment method data array
     */
    protected function getSamplePaymentMethodData(): array
    {
        return [
            'id' => 'method_123',
            'customer' => null,
            'billing' => $this->getSampleAddressData(),
            'type' => 'card',
            'livemode' => true,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'payment_method',
            'status' => 'active',
            'card' => $this->getSamplePaymentCardData(),
            'ach' => null,
        ];
    }

    /**
     * Helper method to get a sample payment card data array
     */
    protected function getSamplePaymentCardData(): array
    {
        return [
            'brand' => 'visa',
            'exp_month' => '01',
            'exp_year' => '30',
            'currency' => 'usd',
            'last_four' => '0000',
        ];
    }

    /**
     * Helper method to get a sample product data array
     */
    protected function getSampleProductData(): array
    {
        return [
            'id' => 'prod_123',
            'name' => 'New Product',
            'image' => null,
            'description' => 'newer product',
            'default_price' => 100,
            'shippable' => false,
            'active' => true,
            'url' => null,
            'metadata' => [],
            'livemode' => true,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'product',
        ];
    }

    /**
     * Helper method to get a sample phase data array
     */
    protected function getSamplePhaseData(): array
    {
        return [
            'id' => 'phase_123',
            'ordinal' => 10,
            'name' => 'New Subscription Phase',
            'pricing_type' => 'static',
            'amount' => 1000,
            'currency' => 'usd',
            'discount_percentage' => null,
            'period_count' => null,
            'livemode' => true,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'subscription_phase',
          ];
    }

    /**
     * Helper method to get a sample subscription data array
     */
    protected function getSampleSubscriptionData(): array
    {
        return [
            'id' => 'sub_123',
            'description' => 'New subscription',
            'current_period_start' => 1640995200,
            'current_period_end' => 1640995200,
            'livemode' => true,
            'currency' => 'usd',
            'status' => 'active',
            'customer' => 'cus_123',
            'default_payment_method' => 'method_123',
            'metadata' => [],
            'start_date' => 1640995200,
            'created' => 1640995200,
            'object' => 'subscription',
            'plan' => null,
          ];
    }

    /**
     * Helper method to get a sample refund data array
     */
    protected function getSampleRefundData(): array
    {
        return [
            'id' => 'ref_123',
            'currency' => 'usd',
            'status' => 'pending',
            'amount' => 100,
            'reason' => 'fraudulent',
            'charge_intent' => null,
            'livemode' => true,
            'created' => 1640995200,
            'updated' => 1640995200,
            'object' => 'refund',
        ];
    }
}
