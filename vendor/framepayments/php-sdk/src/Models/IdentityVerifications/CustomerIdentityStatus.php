<?php

declare(strict_types=1);

namespace Frame\Models\IdentityVerifications;

enum CustomerIdentityStatus: string
{
    case INCOMPLETE = 'incomplete';
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
}
