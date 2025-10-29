<?php

declare(strict_types=1);

namespace Frame\Models\ChargeIntents;

enum AuthorizationMode: string
{
    case AUTOMATIC = 'automatic';
    case MANUAL = 'manual';
}
