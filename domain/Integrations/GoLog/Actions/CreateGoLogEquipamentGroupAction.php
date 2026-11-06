<?php

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogEquipamentGroupDTO;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Delivery\GoLogDriver;

class CreateGoLogEquipamentGroupAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(GoLogDriver $driver, CreateGoLogEquipamentGroupDTO $dto): GoLogDriver
    {
        $vehicle = $driver->vehicle;

        if ($vehicle !== null && $vehicle->golog_equipament_group_id !== null) {
            throw new CultivaException(409, 'Equipment group already created for this driver.');
        }

        if ($driver->getStoppedStep() !== GoLogDriverStep::EQUIPAMENT_GROUP) {
            throw new CultivaException(422, 'Vehicle must be created before creating the equipment group.');
        }

        $group = $this->goLogClient->createEquipamentGroup(
            equipament1Id: $vehicle->golog_tractor_id,
            observation: $dto->observation,
        );

        $vehicle->update([
            'golog_equipament_group_id' => $group->id,
        ]);

        return $driver->load(['company', 'vehicle']);
    }
}
