<?php

declare(strict_types=1);

namespace Frame\Models\Onboarding;

final class OnboardingSessionCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $customerId = null,
        public readonly ?string $entryPoint = null,
        /** @var array<string,mixed>|null */
        public readonly ?array $metadata = null,
        /** @var array<string,mixed>|null */
        public readonly ?array $components = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'customer_id' => $this->customerId,
            'entry_point' => $this->entryPoint,
            'metadata' => $this->metadata,
            'components' => $this->components,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
