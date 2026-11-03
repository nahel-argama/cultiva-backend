<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogVehicleStatus: string
{
    case ATIVO = 'ATIVO';
    case DESATIVADO = 'DESATIVADO';
    case EM_MANUTENCAO = 'EM_MANUTENCAO';
}
