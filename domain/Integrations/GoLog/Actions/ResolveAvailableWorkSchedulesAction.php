<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\AvailableWorkSchedulesDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogWorkScheduleStatus;
use Cultiva\Models\Delivery\GoLogDriver;

class ResolveAvailableWorkSchedulesAction
{
    private const string SCHEDULE_TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(): AvailableWorkSchedulesDTO
    {
        $provisionedDriverIds = $this->findProvisionedDriverIds();

        if ($provisionedDriverIds === []) {
            throw new CultivaException(
                422,
                'No provisioned GoLog drivers available for scheduling.'
            );
        }

        $minScheduleDate = CarbonImmutable::now(self::SCHEDULE_TIMEZONE)->nextWeekday()->toDateString();
        $schedulesByDate = [];

        foreach ($this->goLogClient->listActiveWorkSchedules() as $schedule) {
            if ($schedule->status !== GoLogWorkScheduleStatus::ATIVO) {
                continue;
            }

            if ($schedule->scheduleDate < $minScheduleDate) {
                continue;
            }

            if ($schedule->driverId === null || ! in_array($schedule->driverId, $provisionedDriverIds, true)) {
                continue;
            }

            $schedulesByDate[$schedule->scheduleDate][] = $schedule->id;
        }

        if ($schedulesByDate === []) {
            throw new CultivaException(
                422,
                "No active GoLog work schedules found on or after [{$minScheduleDate}]."
            );
        }

        ksort($schedulesByDate);
        $selectedDate = (string) array_key_first($schedulesByDate);
        $workScheduleIds = array_values(array_unique($schedulesByDate[$selectedDate]));

        return new AvailableWorkSchedulesDTO(
            scheduleDate: CarbonImmutable::parse("{$selectedDate} 00:00:00", self::SCHEDULE_TIMEZONE),
            workScheduleIds: $workScheduleIds,
        );
    }

    /**
     * @return array<int, string>
     */
    private function findProvisionedDriverIds(): array
    {
        /** @var array<int, string> */
        return GoLogDriver::query()
            ->whereNotNull('golog_user_id')
            ->whereNotNull('golog_driver_id')
            ->whereHas('vehicle', fn ($query) => $query
                ->whereNotNull('golog_tractor_id')
                ->whereNotNull('golog_equipament_group_id')
            )
            ->orderBy('id')
            ->pluck('golog_driver_id')
            ->all();
    }
}
