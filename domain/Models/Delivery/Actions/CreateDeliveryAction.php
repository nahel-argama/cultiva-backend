<?php

namespace Cultiva\Models\Delivery\Actions;

use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Delivery\DTOs\DeliveryRegisterDTO;
use Cultiva\Models\Vehicle\Actions\CreateVehicleAction;
use Illuminate\Support\Facades\DB;

class CreateDeliveryAction
{
    public function __construct(
        private readonly CreateVehicleAction $createVehicle,
    ) {}

    public function execute(int $companyId, DeliveryRegisterDTO $dto): Delivery
    {
        return DB::transaction(function () use ($companyId, $dto) {
            $delivery = Delivery::query()->create([
                'company_id'   => $companyId,
                'cnh_number'   => $dto->cnhNumber,
                'cnh_category' => $dto->cnhCategory->value,
            ]);

            $vehicle = $this->createVehicle->execute($delivery->id, $dto->vehicle);
            $delivery->setRelation('vehicle', $vehicle);

            return $delivery;
        });
    }
}
