<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;

final class Billing
{
    public function createMetering(array $params): array
    {
        return Client::post('/v1/billing/metering', $params);
    }

    public function getMetering(string $id): array
    {
        return Client::get('/v1/billing/metering' . "/{$id}");
    }

    public function updateMetering(string $id, array $params): array
    {
        return Client::update('/v1/billing/metering' . "/{$id}", $params);
    }

    public function createMeteringEvent(array $params): array
    {
        return Client::post('/v1/billing/metering_events', $params);
    }

    public function getMeteringEvent(string $id): array
    {
        return Client::get('/v1/billing/metering_events' . "/{$id}");
    }

    public function updateMeteringEvent(string $id, array $params): array
    {
        return Client::update('/v1/billing/metering_events' . "/{$id}", $params);
    }

    public function createBillingInvoice(array $params): array
    {
        return Client::post('/v1/billing/billing_invoice', $params);
    }

    public function createBillingCredit(array $params): array
    {
        return Client::post('/v1/billing/billing_credit', $params);
    }

    public function getBillingCredit(string $id): array
    {
        return Client::get('/v1/billing/billing_credit' . "/{$id}");
    }

    public function getCustomerReport(array $params = []): array
    {
        return Client::get('/v1/billing/report/customer', $params);
    }

    public function getEventReport(string $eventName, array $params = []): array
    {
        return Client::get("/v1/billing/report/event/{$eventName}", $params);
    }

    public function getEventsReport(array $params = []): array
    {
        return Client::get('/v1/billing/report/events', $params);
    }

    public function getSubscriptionReport(array $params = []): array
    {
        return Client::get('/v1/billing/report/subscription', $params);
    }
}
