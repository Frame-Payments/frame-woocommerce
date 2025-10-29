<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

enum PaymentMethodStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}
