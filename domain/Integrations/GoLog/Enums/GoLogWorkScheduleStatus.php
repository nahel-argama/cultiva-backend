<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogWorkScheduleStatus: string
{
    case ATIVO = 'ATIVO';
    case DESATIVADO = 'DESATIVADO';
    case EM_OPERACAO = 'EM_OPERACAO';
}
