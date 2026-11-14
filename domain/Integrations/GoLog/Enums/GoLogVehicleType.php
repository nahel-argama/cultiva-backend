<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogVehicleType: string
{
    case MOTO = 'MOTO';
    case CARRO = 'CARRO';
    case UTILITARIO = 'UTILITARIO';
    case VAN = 'VAN';
    case VUC = 'VUC';
    case TOCO = 'TOCO';
    case TRUCK = 'TRUCK';
    case BITRUCK = 'BITRUCK';
    case CAVALO_MECANICO = 'CAVALO_MECANICO';

    public function isRigid(): bool
    {
        return $this !== self::CAVALO_MECANICO;
    }
}
