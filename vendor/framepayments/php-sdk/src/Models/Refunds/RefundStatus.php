<?php

declare(strict_types=1);

namespace Frame\Models\Refunds;

enum RefundStatus: string
{
    case PENDING = 'pending';
    case REQUIRES_ACTION = 'requires_action';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}
