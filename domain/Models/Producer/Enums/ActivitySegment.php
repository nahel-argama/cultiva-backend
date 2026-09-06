<?php

namespace Cultiva\Models\Producer\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum ActivitySegment: string
{
    use HasEnumLabel;

    case VEGETABLES = 'vegetables';
    case FRUITS = 'fruits';
    case TUBERS_ROOTS = 'tubers_roots';
    case HERBS_SPICES = 'herbs_spices';
    case GRAINS = 'grains';
    case SPECIALTY = 'specialty';
}
