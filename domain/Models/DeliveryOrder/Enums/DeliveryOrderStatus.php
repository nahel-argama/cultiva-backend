<?php

namespace Cultiva\Models\DeliveryOrder\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum DeliveryOrderStatus: string
{
    use HasEnumLabel;

    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
