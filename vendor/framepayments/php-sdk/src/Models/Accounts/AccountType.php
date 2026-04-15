<?php

declare(strict_types=1);

namespace Frame\Models\Accounts;

enum AccountType: string
{
    case INDIVIDUAL = 'individual';
    case BUSINESS = 'business';
    case MERCHANT = 'merchant';
}
