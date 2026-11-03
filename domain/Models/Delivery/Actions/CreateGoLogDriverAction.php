<?php

namespace Cultiva\Models\Delivery\Actions;

use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Models\Delivery\DTOs\GoLogDriverRegisterDTO;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\User\User;
use Cultiva\Models\Vehicle\GoLogVehicle;
use Illuminate\Support\Facades\DB;

class CreateGoLogDriverAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(User $user, int $companyId, string $password, GoLogDriverRegisterDTO $dto): GoLogDriver
    {
        return DB::transaction(function () use ($user, $companyId, $password, $dto): GoLogDriver {
            $goLogUser = $this->goLogClient->createUser(
                name: $user->name,
                email: $user->email,
                password: $password,
                cpf: $dto->cpf,
            );

            $goLogDriverResponse = $this->goLogClient->createDriver(
                userId: $goLogUser->id,
                cnhNumber: $dto->cnhNumber,
                cnhExpiration: $dto->cnhExpiration,
                costPerHour: $dto->costPerHour,
            );

            $goLogTractor = $this->goLogClient->createTractor(
                plate: $dto->vehicle->plate,
                renavam: $dto->vehicle->renavam,
                model: $dto->vehicle->model,
                numberAxles: $dto->vehicle->numberAxles,
                maximumCapacity: $dto->vehicle->capacity,
                typeFuel: $dto->vehicle->typeFuel,
                costPerKilometer: $dto->vehicle->costPerKilometer,
            );

            $goLogGroup = $this->goLogClient->createEquipamentGroup(
                equipament1Id: $goLogTractor->id,
            );

            $gologDriver = GoLogDriver::query()->create([
                'company_id' => $companyId,
                'golog_user_id' => $goLogUser->id,
                'golog_driver_id' => $goLogDriverResponse->id,
            ]);

            $gologVehicle = GoLogVehicle::query()->create([
                'golog_driver_id' => $gologDriver->id,
                'golog_tractor_id' => $goLogTractor->id,
                'golog_equipament_group_id' => $goLogGroup->id,
            ]);

            $gologDriver->setRelation('vehicle', $gologVehicle);

            return $gologDriver;
        });
    }
}
