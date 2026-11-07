<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogOperationType: string
{
    case COLETA = 'COLETA';
    case ENTREGA = 'ENTREGA';
}
