<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogFuelType: string
{
    case DIESEL = 'DIESEL';
    case ETANOL = 'ETANOL';
    case GASOLINA = 'GASOLINA';
    case FLEX = 'FLEX';
    case GNV = 'GNV';
    case ELETRICO = 'ELETRICO';
}
