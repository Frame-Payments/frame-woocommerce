<?php

declare(strict_types=1);

namespace Frame\Models\OnboardingSessions;

final class OnboardingSessionListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $meta,
        public readonly array $sessions,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            sessions: isset($p['data']) && is_array($p['data'])
                ? array_map(fn (array $s) => OnboardingSession::fromArray($s), $p['data'])
                : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'data' => $this->sessions,
        ];
    }
}
