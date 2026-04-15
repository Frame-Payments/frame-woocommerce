<?php

declare(strict_types=1);

namespace Frame\Models\Accounts;

final class AccountListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly array $meta,
        public readonly array $accounts,
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            accounts: isset($p['data']) && is_array($p['data'])
                ? array_map(fn (array $a) => Account::fromArray($a), $p['data'])
                : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'data' => $this->accounts,
        ];
    }
}
