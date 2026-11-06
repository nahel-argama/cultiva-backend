<?php

namespace Cultiva\Models\Delivery\Transformers;

use Cultiva\Models\Delivery\GoLogDriver;

final class GoLogDriverTransformer
{
    public function transform(GoLogDriver $gologDriver): array
    {
        $vehicle = $gologDriver->vehicle;

        return [
            'id' => $gologDriver->id,
            'cpf' => $gologDriver->cpf,
            'trade_name' => $gologDriver->company->trade_name,
            'golog_user_id' => $gologDriver->golog_user_id,
            'golog_driver_id' => $gologDriver->golog_driver_id,
            'golog_work_schedule_id' => $gologDriver->golog_work_schedule_id,
            'stopped_step' => $gologDriver->getStoppedStep()->value,
            'is_provisioned' => $gologDriver->isProvisioned(),
            'vehicle' => $vehicle !== null ? [
                'id' => $vehicle->id,
                'golog_tractor_id' => $vehicle->golog_tractor_id,
                'golog_equipament_group_id' => $vehicle->golog_equipament_group_id,
            ] : null,
        ];
    }
}
