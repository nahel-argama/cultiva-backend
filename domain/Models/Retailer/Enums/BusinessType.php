<?php

namespace Cultiva\Models\Retailer\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum BusinessType: string
{
    use HasEnumLabel;

    case SUPERMARKET = 'supermarket';
    case HORTIFRUTI = 'hortifruti';
    case RESTAURANT = 'restaurant';
}
