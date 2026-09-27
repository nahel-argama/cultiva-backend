<?php

namespace Cultiva\Models\DeliveryTrip\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum DeliveryTripStopType: string
{
    use HasEnumLabel;

    case PICKUP = 'pickup';
    case DROPOFF = 'dropoff';
}
