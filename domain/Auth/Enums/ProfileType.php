<?php

namespace Cultiva\Auth\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum ProfileType: string
{
    use HasEnumLabel;

    case PRODUCER = 'producer';
    case RETAILER = 'retailer';
    case DELIVERY = 'delivery';
}
