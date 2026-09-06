<?php

namespace Cultiva\Models\Producer\DTOs;

use Cultiva\Models\Producer\Enums\ActivitySegment;

final class ProducerRegisterDTO
{
    public function __construct(
        public readonly ActivitySegment $activitySegment,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            activitySegment: ActivitySegment::from($data['activity_segment']),
        );
    }
}
