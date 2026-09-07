<?php

namespace Cultiva\Models\Delivery\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum CnhCategory: string
{
    use HasEnumLabel;

    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
    case AB = 'AB';
    case AC = 'AC';
    case AD = 'AD';
    case AE = 'AE';
}
