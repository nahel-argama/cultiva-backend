<?php

namespace Cultiva\Models\DeliveryTrip\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum DeliveryTripStopStatus: string
{
    use HasEnumLabel;

    case PENDING = 'pending';
    case ARRIVED = 'arrived';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';
}
