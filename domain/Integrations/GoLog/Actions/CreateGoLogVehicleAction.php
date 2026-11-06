<?php

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogVehicleDTO;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\Vehicle\GoLogVehicle;

class CreateGoLogVehicleAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(GoLogDriver $driver, CreateGoLogVehicleDTO $dto): GoLogDriver
    {
        if ($driver->vehicle !== null && $driver->vehicle->golog_tractor_id !== null) {
            throw new CultivaException(409, 'Vehicle already created for this driver.');
        }

        if ($driver->getStoppedStep() !== GoLogDriverStep::TRACTOR) {
            throw new CultivaException(422, 'GoLog driver must be created before creating the vehicle.');
        }

        $tractor = $this->goLogClient->createTractor(
            plate: $dto->plate,
            renavam: $dto->renavam,
            model: $dto->model,
            numberAxles: $dto->numberAxles,
            maximumCapacity: $dto->capacity,
            typeFuel: $dto->typeFuel,
            costPerKilometer: $dto->costPerKilometer,
        );

        GoLogVehicle::query()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => $tractor->id,
        ]);

        return $driver->refresh()->load(['company', 'vehicle']);
    }
}
