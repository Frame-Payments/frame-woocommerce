<?php

declare(strict_types=1);

namespace Frame\Models\SubscriptionPhases;

enum PhasePricingType: string
{
    case STATIC = 'static';
    case RELATIVE = 'relative';
}
