<?php

declare(strict_types=1);

namespace Frame\Models\Onboarding;

final class OnboardingSession implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $userId,
        public readonly ?string $customerId,
        public readonly ?string $status,
        public readonly ?array $steps,
        public readonly ?array $components,
        public readonly ?string $entryPoint,
        public readonly ?array $metadata,
        public readonly ?string $clientSecret,
        public readonly int $expiresAt,
        public readonly int $createdAt,
        public readonly int $updatedAt,
        public readonly ?int $completedAt,
        public readonly string $object,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            id: $p['id'],
            userId: $p['user_id'] ?? null,
            customerId: $p['customer_id'] ?? null,
            status: $p['status'] ?? null,
            steps: isset($p['steps']) && is_array($p['steps']) ? $p['steps'] : null,
            components: isset($p['components']) && is_array($p['components']) ? $p['components'] : null,
            entryPoint: $p['entry_point'] ?? null,
            metadata: isset($p['metadata']) && is_array($p['metadata']) ? $p['metadata'] : null,
            clientSecret: $p['client_secret'] ?? null,
            expiresAt: (int)$p['expires_at'],
            createdAt: (int)$p['created_at'],
            updatedAt: (int)$p['updated_at'],
            completedAt: isset($p['completed_at']) ? (int)$p['completed_at'] : null,
            object: $p['object'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'customer_id' => $this->customerId,
            'status' => $this->status,
            'steps' => $this->steps,
            'components' => $this->components,
            'entry_point' => $this->entryPoint,
            'metadata' => $this->metadata,
            'client_secret' => $this->clientSecret,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'completed_at' => $this->completedAt,
            'object' => $this->object,
        ];
    }
}
