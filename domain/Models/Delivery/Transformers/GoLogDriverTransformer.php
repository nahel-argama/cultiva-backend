<?php

namespace Cultiva\Models\Delivery\Transformers;

use Cultiva\Models\Delivery\GoLogDriver;

final class GoLogDriverTransformer
{
    public function transform(GoLogDriver $gologDriver): array
    {
        return [
            'trade_name' => $gologDriver->company->trade_name,
            'golog_user_id' => $gologDriver->golog_user_id,
            'golog_driver_id' => $gologDriver->golog_driver_id,
            'vehicle' => [
                'golog_tractor_id' => $gologDriver->vehicle->golog_tractor_id,
                'golog_equipament_group_id' => $gologDriver->vehicle->golog_equipament_group_id,
            ],
        ];
    }
}
