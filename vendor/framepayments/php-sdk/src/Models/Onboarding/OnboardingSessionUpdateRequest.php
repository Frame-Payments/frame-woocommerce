<?php

declare(strict_types=1);

namespace Frame\Models\Onboarding;

final class OnboardingSessionUpdateRequest implements \JsonSerializable
{
    public function __construct(
        /** @var array<string,mixed>|null */
        public readonly ?array $steps = null,
        public readonly ?string $status = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'steps' => $this->steps,
            'status' => $this->status,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
