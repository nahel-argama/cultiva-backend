<?php

namespace Cultiva\Models\Retailer\Actions;

use Cultiva\Models\Retailer\DTOs\RetailerRegisterDTO;
use Cultiva\Models\Retailer\Retailer;

class CreateRetailerAction
{
    public function execute(int $companyId, RetailerRegisterDTO $dto): Retailer
    {
        return Retailer::query()->create([
            'company_id'    => $companyId,
            'business_type' => $dto->businessType->value,
        ]);
    }
}
