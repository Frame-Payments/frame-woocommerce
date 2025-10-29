<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

final class SubscriptionListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly ?array $meta,
        public readonly array $subscriptions
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            subscriptions: isset($p['data']) && is_array($p['data']) ? array_map(fn (array $pm) => Subscription::fromArray($pm), $p['data']) : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'data' => $this->subscriptions,
        ];
    }
}
