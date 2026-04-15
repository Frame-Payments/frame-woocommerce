<?php

declare(strict_types=1);

namespace Frame\Models\Onboarding;

final class OnboardingSessionListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $sessions,
        public readonly bool $hasMore,
        public readonly string $object,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            sessions: isset($p['data']) && is_array($p['data'])
                ? array_map(fn (array $s) => OnboardingSession::fromArray($s), $p['data'])
                : [],
            hasMore: (bool)($p['has_more'] ?? false),
            object: $p['object'] ?? 'list',
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => $this->sessions,
            'has_more' => $this->hasMore,
            'object' => $this->object,
        ];
    }
}
