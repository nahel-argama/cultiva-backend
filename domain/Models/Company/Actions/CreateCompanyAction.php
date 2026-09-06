<?php

namespace Cultiva\Models\Company\Actions;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Address\Action\CreateAddressAction;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Company\DTOs\CompanyRegisterDTO;
use Illuminate\Support\Facades\DB;

class CreateCompanyAction
{
    public function __construct(
        private readonly CreateAddressAction $createAddress,
    ) {}

    public function execute(int $userId, CompanyRegisterDTO $dto, GeoAddressDTO $geo): Company
    {
        return DB::transaction(function () use ($dto, $userId, $geo) {
            $company = Company::query()->create([
                'user_id'         => $userId,
                'document_number' => $dto->documentNumber,
                'trade_name'      => $dto->tradeName,
                'legal_name'      => $dto->legalName,
                'phone'           => $dto->phone,
            ]);

            $address = $this->createAddress->execute($company, $dto->address, $geo);

            $company->setRelation('address', $address);

            return $company;
        });
    }
}
