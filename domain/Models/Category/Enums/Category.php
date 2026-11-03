<?php

declare(strict_types=1);

namespace Cultiva\Models\Category\Enums;

use Cultiva\Base\Traits\HasEnumLabel;

enum Category: string
{
    use HasEnumLabel;

    case FRUTAS = 'Frutas';
    case LEGUMES = 'Legumes';
    case VERDURAS = 'Verduras';
    case TUBERCULOS_E_RAIZES = 'Tubérculos e raízes';
    case GRAOS_E_CEREAIS = 'Grãos e cereais';
}
