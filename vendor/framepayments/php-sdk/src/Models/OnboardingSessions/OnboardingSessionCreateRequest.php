<?php

declare(strict_types=1);

namespace Frame\Models\OnboardingSessions;

final class OnboardingSessionCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly string $accountId,
        public readonly ?string $returnUrl = null,
        /** @var string[]|null */
        public readonly ?array $steps = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'account_id' => $this->accountId,
            'return_url' => $this->returnUrl,
            'steps' => $this->steps,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
