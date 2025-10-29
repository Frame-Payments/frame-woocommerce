<?php

declare(strict_types=1);

namespace Frame\Models\Subscriptions;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case TERMINATED = 'terminated';
    case CANCELED = 'canceled';
}
