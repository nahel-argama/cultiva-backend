<?php

namespace Cultiva\Models\Producer\Transformers;

use Cultiva\Models\Producer\Producer;

final class ProducerTransformer
{
    public function transform(Producer $producer): array
    {
        return [
            'trade_name'       => $producer->company->trade_name,
            'activity_segment' => $producer->activity_segment->value,
        ];
    }
}
