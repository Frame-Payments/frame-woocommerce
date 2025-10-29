<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

enum ChargeIntentStatus: string
{
    case INCOMPLETE = 'incomplete';
    case PENDING = 'pending';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';
    case REVERSED = 'reversed';
    case FAILED = 'failed';
    case DISPUTED = 'disputed';
    case SUCCEEDED = 'succeeded';
}
