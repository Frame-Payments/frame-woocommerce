<?php

declare(strict_types=1);

namespace Frame\Endpoints;

use Frame\Client;
use Frame\Models\OnboardingSessions\OnboardingSession;
use Frame\Models\OnboardingSessions\OnboardingSessionCreateRequest;
use Frame\Models\OnboardingSessions\OnboardingSessionListResponse;

final class OnboardingSessions
{
    private const BASE_PATH = '/v1/onboarding_sessions';

    public function create(OnboardingSessionCreateRequest $params): OnboardingSession
    {
        $json = Client::post(self::BASE_PATH, $params->toArray());

        return OnboardingSession::fromArray($json);
    }

    public function list(string $accountId): OnboardingSessionListResponse
    {
        $json = Client::get(self::BASE_PATH, ['account_id' => $accountId]);

        return OnboardingSessionListResponse::fromArray($json);
    }
}
