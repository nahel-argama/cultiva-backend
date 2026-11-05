<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogShipmentStatus: string
{
    case PENDENTE = 'PENDENTE';
    case AGUARDANDO_INICIO = 'AGUARDANDO_INICIO';
    case INICIADO = 'INICIADO';
    case FINALIZADO = 'FINALIZADO';
    case CANCELADO = 'CANCELADO';
}
