<?php

declare(strict_types=1);

namespace Frame\Models\Products;

enum ProductPurchaseType: string
{
    case ONE_TIME = 'one_time';
    case RECURRING = 'recurring';
}
