<?php

declare(strict_types=1);

namespace Frame\Models\PaymentMethods;

enum PaymentMethodType: string
{
    case CARD = 'card';
    case ACH = 'ach';
}
