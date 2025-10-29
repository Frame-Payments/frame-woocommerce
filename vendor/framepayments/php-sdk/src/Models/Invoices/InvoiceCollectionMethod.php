<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

enum InvoiceCollectionMethod: string
{
    case AUTO_CHARGE = 'auto_charge';
    case REQUEST_PAYMENT = 'request_payment';
}
