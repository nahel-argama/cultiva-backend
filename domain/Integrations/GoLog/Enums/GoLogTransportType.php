<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

use Cultiva\Models\CargoType\Enums\CargoType;

enum GoLogTransportType: string
{
    case CARGA_SECA = 'CARGA SECA';
    case CLIMATIZADO = 'CLIMATIZADO';
    case REFRIGERADO = 'REFRIGERADO';

    public static function fromCargoType(CargoType $cargoType): self
    {
        return match ($cargoType) {
            CargoType::DRY => self::CARGA_SECA,
            CargoType::CLIMATE_CONTROLLED => self::CLIMATIZADO,
            CargoType::REFRIGERATED => self::REFRIGERADO,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CARGA_SECA => 'Transporte de cargas secas nao pereciveis',
            self::CLIMATIZADO => 'Transporte sob temperatura amena controlada',
            self::REFRIGERADO => 'Transporte refrigerado para alta perecibilidade',
        };
    }

    public function care(): string
    {
        return match ($this) {
            self::CARGA_SECA => 'Manipular com cuidado',
            self::CLIMATIZADO => 'Manter temperatura controlada sem congelamento',
            self::REFRIGERADO => 'Manter refrigeracao continua entre dois e oito graus',
        };
    }
}
