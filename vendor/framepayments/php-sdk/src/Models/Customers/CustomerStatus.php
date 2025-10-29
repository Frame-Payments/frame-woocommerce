<?php

declare(strict_types=1);

namespace Frame\Models\Customers;

enum CustomerStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}
