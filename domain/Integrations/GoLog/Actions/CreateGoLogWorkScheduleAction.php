<?php

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogWorkScheduleDTO;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Delivery\GoLogDriver;

class CreateGoLogWorkScheduleAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(GoLogDriver $driver, CreateGoLogWorkScheduleDTO $dto): GoLogDriver
    {
        if ($driver->golog_work_schedule_id !== null) {
            throw new CultivaException(409, 'Driver already has a work schedule.');
        }

        if ($driver->getStoppedStep() !== GoLogDriverStep::WORK_SCHEDULE) {
            throw new CultivaException(422, 'Equipment group must be created before creating the work schedule.');
        }

        $schedule = $this->goLogClient->createWorkSchedule(
            driverId: $driver->golog_driver_id,
            equipamentGroupId: $driver->vehicle->golog_equipament_group_id,
            scheduleDate: $dto->scheduleDate,
            startHour: $dto->startHour,
            startMinute: $dto->startMinute,
            endHour: $dto->endHour,
            endMinute: $dto->endMinute,
        );

        $driver->update([
            'golog_work_schedule_id' => $schedule->id,
        ]);

        return $driver->load(['company', 'vehicle']);
    }
}
