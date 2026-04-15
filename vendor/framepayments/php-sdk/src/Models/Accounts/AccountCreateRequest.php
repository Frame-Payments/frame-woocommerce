<?php

declare(strict_types=1);

namespace Frame\Models\Accounts;

final class AccountCreateRequest implements \JsonSerializable
{
    public function __construct(
        public readonly AccountType $type,
        public readonly ?string $externalId = null,
        /** @var array{token?: string, accepted_at?: string, ip_address?: string, user_agent?: string}|null */
        public readonly ?array $termsOfService = null,
        /** @var array<string,mixed>|null */
        public readonly ?array $metadata = null,
        /** @var string[]|null */
        public readonly ?array $capabilities = null,
        /** @var array{individual?: array<string,mixed>, business?: array<string,mixed>}|null */
        public readonly ?array $profile = null,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'type' => $this->type->value,
            'external_id' => $this->externalId,
            'terms_of_service' => $this->termsOfService,
            'metadata' => $this->metadata,
            'capabilities' => $this->capabilities,
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
