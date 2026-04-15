<?php

declare(strict_types=1);

namespace Frame\Models\OnboardingSessions;

final class OnboardingSession implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $accountId,
        public readonly ?string $clientSecret,
        public readonly ?string $returnUrl,
        public readonly array $steps,
        public readonly ?int $expiresAt,
        public readonly ?string $url,
        public readonly bool $livemode,
        public readonly string $object,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            id: $p['id'],
            accountId: $p['account_id'],
            clientSecret: $p['client_secret'] ?? null,
            returnUrl: $p['return_url'] ?? null,
            steps: isset($p['steps']) && is_array($p['steps']) ? $p['steps'] : [],
            expiresAt: isset($p['expires_at']) ? (int)$p['expires_at'] : null,
            url: $p['url'] ?? null,
            livemode: (bool)$p['livemode'],
            object: $p['object'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'client_secret' => $this->clientSecret,
            'return_url' => $this->returnUrl,
            'steps' => $this->steps,
            'expires_at' => $this->expiresAt,
            'url' => $this->url,
            'livemode' => $this->livemode,
            'object' => $this->object,
        ];
    }
}
