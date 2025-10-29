<?php

declare(strict_types=1);

namespace Frame\Models\Products;

enum ProductRecurringInterval: string
{
    case DAILY = 'daily';
    case MONTHLY = 'monthly';
    case WEEKLY = 'weekly';
    case YEARLY = 'yearly';
    case EVERY_THREE_MONTHS = 'every_three_months';
    case EVERY_SIX_MONTHS = 'every_six_months';
}
