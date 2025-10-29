<?php

declare(strict_types=1);

namespace Frame\Models\Disputes;

enum DisputeReason: string
{
    case BANK_CANNOT_PROCESS = 'bank_cannot_process';
    case CHECK_RETURNED = 'check_returned';
    case CREDIT_NOT_PROCESSED = 'credit_not_processed';
    case CUSTOMER_INITIATED = 'customer_initiated';
    case DEBIT_NOT_AUTHORIZED = 'debit_not_authorized';
    case DUPLICATE = 'duplicate';
    case FRAUDULENT = 'fraudulent';
    case GENERAL = 'general';
    case INCORRECT_ACCOUNT_DETAILS = 'incorrect_account_details';
    case INSUFFICIENT_FUNDS = 'insufficient_funds';
    case PRODUCT_NOT_RECEIVED = 'product_not_received';
    case PRODUCT_UNACCEPTABLE = 'product_unacceptable';
    case SUBSCRIPTION_CANCELED = 'subscription_canceled';
    case UNRECOGNIZED = 'unrecognized';
}
