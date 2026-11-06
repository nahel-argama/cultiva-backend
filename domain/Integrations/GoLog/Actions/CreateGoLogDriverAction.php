<?php

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogDriverDTO;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Delivery\GoLogDriver;

class CreateGoLogDriverAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(GoLogDriver $driver, CreateGoLogDriverDTO $dto): GoLogDriver
    {
        if ($driver->golog_driver_id !== null) {
            throw new CultivaException(409, 'GoLog driver already created.');
        }

        if ($driver->getStoppedStep() !== GoLogDriverStep::DRIVER) {
            throw new CultivaException(422, 'GoLog user must be created before creating the driver.');
        }

        $goLogDriver = $this->goLogClient->createDriver(
            userId: $driver->golog_user_id,
            cnhNumber: $dto->cnhNumber,
            cnhExpiration: $dto->cnhExpiration,
            costPerHour: $dto->costPerHour,
        );

        $driver->update([
            'golog_driver_id' => $goLogDriver->id,
        ]);

        return $driver->load(['company', 'vehicle']);
    }
}
