<?php

declare(strict_types=1);

namespace Frame\Models\Disputes;

enum DisputeStatus: string
{
    case WARNING_NEEDS_RESPONSE = 'warning_needs_response';
    case WARNING_UNDER_REVIEW = 'warning_under_review';
    case WARNING_CLOSED = 'warning_closed';
    case NEEDS_RESPONSE = 'needs_response';
    case UNDER_REVIEW = 'under_review';
    case WON = 'won';
    case LOST = 'lost';
}
