<?php

declare(strict_types=1);

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\SignupMetadataDTO;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Delivery\Enums\CnhCategory;
use Cultiva\Models\Producer\Enums\ActivitySegment;
use Cultiva\Models\Retailer\Enums\BusinessType;
use Cultiva\Models\Vehicle\Enums\CargoType;

class GetSignupMetadataAction
{
    public function execute(): SignupMetadataDTO
    {
        return new SignupMetadataDTO(
            profileType: ProfileType::options(),
            activitySegment: ActivitySegment::options(),
            businessType: BusinessType::options(),
            cnhCategory: CnhCategory::options(),
            cargoType: CargoType::options(),
        );
    }
}
