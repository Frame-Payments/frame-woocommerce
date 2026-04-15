<?php

declare(strict_types=1);

namespace Frame\Models\Accounts;

final class AccountUpdateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly ?string $externalId = null,
        /** @var array{token?: string, accepted_at?: string, ip_address?: string, user_agent?: string}|null */
        public readonly ?array $termsOfService = null,
        /** @var array<string,mixed>|null */
        public readonly ?array $metadata = null,
        /** @var array{individual?: array<string,mixed>, business?: array<string,mixed>}|null */
        public readonly ?array $profile = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'external_id' => $this->externalId,
            'terms_of_service' => $this->termsOfService,
            'metadata' => $this->metadata,
            'profile' => $this->profile,
        ];

        $filterNulls = fn ($v) => $v !== null;

        return array_filter($payload, $filterNulls);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
