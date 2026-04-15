<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\Onboarding\OnboardingSession;
use Frame\Models\Onboarding\OnboardingSessionCreateRequest;
use Frame\Models\Onboarding\OnboardingSessionListResponse;
use Frame\Models\Onboarding\OnboardingSessionUpdateRequest;

final class Onboarding
{
    private const BASE_PATH = '/v1/onboarding/sessions';

    public function list(array $params = []): OnboardingSessionListResponse
    {
        $json = Client::get(self::BASE_PATH, $params);

        return OnboardingSessionListResponse::fromArray($json);
    }

    public function create(OnboardingSessionCreateRequest $params): OnboardingSession
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return OnboardingSession::fromArray($json);
    }

    public function retrieve(string $sessionId): OnboardingSession
    {
        $json = Client::get(self::BASE_PATH . "/{$sessionId}");

        return OnboardingSession::fromArray($json);
    }

    public function update(string $sessionId, OnboardingSessionUpdateRequest $params): OnboardingSession
    {
        $json = Client::update(self::BASE_PATH . "/{$sessionId}", $params->toArray());

        return OnboardingSession::fromArray($json);
    }

    public function payout(string $sessionId, array $params = []): OnboardingSession
    {
        $json = Client::post(self::BASE_PATH . "/{$sessionId}/payout", $params);

        return OnboardingSession::fromArray($json);
    }
}
