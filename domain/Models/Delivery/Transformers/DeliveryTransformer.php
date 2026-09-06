<?php

namespace Cultiva\Models\Delivery\Transformers;

use Cultiva\Models\Address\Transformers\AddressTransformer;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Vehicle\Transformers\VehicleTransformer;

final class DeliveryTransformer
{
    public function __construct(
        private readonly AddressTransformer $addressTransformer,
        private readonly VehicleTransformer $vehicleTransformer,
    ) {}

    public function transform(Delivery $delivery): array
    {
        $company = $delivery->company;

        return [
            'trade_name'      => $company->trade_name,
            'legal_name'      => $company->legal_name,
            'document_number' => $company->document_number,
            'phone'           => $company->phone,
            'cnh_number'      => $delivery->cnh_number,
            'cnh_category'    => $delivery->cnh_category->value,
            'vehicle'         => $this->vehicleTransformer->transform($delivery->vehicle),
            'address'         => $this->addressTransformer->transform($company->address),
        ];
    }
}
