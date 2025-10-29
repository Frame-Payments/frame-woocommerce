<?php

declare(strict_types=1);

namespace Frame\Models\SubscriptionPhases;

final class PhaseListResponse implements \JsonSerializable
{
    public function __construct(
        public readonly ?array $meta,
        public readonly array $phases
    ) {
    }

    public static function fromArray(array $p): self
    {
        return new self(
            meta: isset($p['meta']) && is_array($p['meta']) ? $p['meta'] : [],
            phases: isset($p['phases']) && is_array($p['phases']) ? array_map(fn (array $pm) => SubscriptionPhase::fromArray($pm), $p['phases']) : [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'meta' => $this->meta,
            'phases' => $this->phases,
        ];
    }
}
