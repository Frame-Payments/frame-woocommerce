<?php

declare(strict_types=1);

namespace Frame\Models\Accounts;

enum AccountStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case RESTRICTED = 'restricted';
    case DISABLED = 'disabled';
}
