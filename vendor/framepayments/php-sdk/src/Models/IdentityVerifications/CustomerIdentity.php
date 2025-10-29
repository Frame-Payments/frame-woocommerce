<?php

declare(strict_types=1);

namespace Frame\Models\IdentityVerifications;

final class CustomerIdentity implements \JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly ?CustomerIdentityStatus $status,
        public readonly ?string $verificationURL,
        public readonly ?int $pending,
        public readonly ?int $verified,
        public readonly ?int $failed,
        public readonly int $created,
        public readonly int $updated,
        public readonly string $object
    ) {
    }

    public static function fromArray(array $p): self
    {
        $status = null;
        if (isset($p['status'])) {
            $status = CustomerIdentityStatus::tryFrom($p['status']);
            if ($status === null) {
                error_log("Unexpected CustomerIdentityStatus: " . $p['status']);
            }
        }

        return new self(
            id: $p['id'],
            status: $status,
            verificationURL: $p['verification_url'] ?? null,
            created: (int)$p['created'],
            updated: (int)$p['updated'],
            object: $p['object'],
            pending: (int)$p['pending'] ?? null,
            verified: (int)$p['verified'] ?? null,
            failed: (int)$p['failed'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'verification_url' => $this->verificationURL,
            'pending' => $this->pending,
            'verified' => $this->verified,
            'failed' => $this->failed,
            'created' => $this->created,
            'updated' => $this->updated,
            'object' => $this->object,
        ];
    }
}
