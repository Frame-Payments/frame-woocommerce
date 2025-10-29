<?php

declare(strict_types=1);

namespace Frame\Models\Invoices;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case OUTSTANDING = 'outstanding';
    case DUE = 'due';
    case OVERDUE = 'overdue';
    case PAID = 'paid';
    case WRITTEN_OFF = 'written_off';
    case VOIDED = 'voided';
}
