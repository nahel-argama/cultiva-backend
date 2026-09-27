<?php

namespace Cultiva\Models\DeliveryTrip\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum DeliveryTripStatus: string
{
    use HasEnumLabel;

    case AVAILABLE = 'available';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
