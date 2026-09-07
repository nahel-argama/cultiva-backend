<?php

namespace Cultiva\Models\Vehicle\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum CargoType: string
{
    use HasEnumLabel;

    case DRY = 'dry';
    case CLIMATE_CONTROLLED = 'climate_controlled';
    case REFRIGERATED = 'refrigerated';
}
