<?php

namespace Cultiva\Integrations\GoLog\DTO;

use Carbon\CarbonImmutable;

final readonly class CreateGoLogWorkScheduleDTO
{
    public function __construct(
        public string $scheduleDate,
        public int $startHour,
        public int $startMinute,
        public int $endHour,
        public int $endMinute,
    ) {}

    public static function from(array $data): self
    {
        $start = CarbonImmutable::createFromFormat('H:i', $data['start_time']);
        $end = CarbonImmutable::createFromFormat('H:i', $data['end_time']);

        return new self(
            scheduleDate: $data['schedule_date'],
            startHour: $start->hour,
            startMinute: $start->minute,
            endHour: $end->hour,
            endMinute: $end->minute,
        );
    }
}
