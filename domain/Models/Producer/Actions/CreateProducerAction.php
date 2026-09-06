<?php

namespace Cultiva\Models\Producer\Actions;

use Cultiva\Models\Producer\DTOs\ProducerRegisterDTO;
use Cultiva\Models\Producer\Producer;

class CreateProducerAction
{
    public function execute(int $companyId, ProducerRegisterDTO $dto): Producer
    {
        return Producer::query()->create([
            'company_id'       => $companyId,
            'activity_segment' => $dto->activitySegment->value,
        ]);
    }
}
