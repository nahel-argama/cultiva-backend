<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogWorkScheduleResponseDTO;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Illuminate\Database\Eloquent\Collection;

class OptimizeDeliveryRoutesAction
{
    private const string SCHEDULE_TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(?string $targetDate = null): OptimizeRoutesResultDTO
    {
        /** @var Collection<int, GoLogShipment> $shipments */
        $shipments = GoLogShipment::query()
            ->where('status', GoLogShipmentStatus::PENDENTE)
            ->orderBy('id')
            ->get();

        if ($shipments->isEmpty()) {
            return new OptimizeRoutesResultDTO(
                shipmentsOptimized: 0,
                routesCount: 0,
                workSchedulesUsed: 0,
                plannedShipmentIds: [],
                skippedShipmentIds: [],
                transportIds: [],
                workScheduleIds: [],
            );
        }

        /** @var array<int, string> $provisionedDriverIds */
        $provisionedDriverIds = GoLogDriver::query()
            ->whereNotNull('golog_user_id')
            ->whereNotNull('golog_driver_id')
            ->whereNotNull('golog_work_schedule_id')
            ->whereHas('vehicle', fn ($query) => $query
                ->whereNotNull('golog_tractor_id')
                ->whereNotNull('golog_equipament_group_id')
            )
            ->orderBy('id')
            ->pluck('golog_driver_id')
            ->all();

        if ($provisionedDriverIds === []) {
            throw new CultivaException(
                422,
                'No provisioned GoLog drivers with active work schedules available for route optimization.'
            );
        }

        $scheduleDate = $targetDate ?? CarbonImmutable::now(self::SCHEDULE_TIMEZONE)->nextWeekday()->toDateString();

        $workScheduleIds = array_values(array_map(
            static fn (GoLogWorkScheduleResponseDTO $schedule): string => $schedule->id,
            array_filter(
                $this->goLogClient->listActiveWorkSchedules(),
                static fn (GoLogWorkScheduleResponseDTO $schedule): bool => $schedule->scheduleDate === $scheduleDate
                    && in_array($schedule->driverId, $provisionedDriverIds, true)
            )
        ));

        if ($workScheduleIds === []) {
            throw new CultivaException(
                422,
                "No active GoLog work schedules found for date [{$scheduleDate}]."
            );
        }

        /** @var array<int, string> $shipmentIds */
        $shipmentIds = $shipments->pluck('golog_shipment_id')->all();

        $apiResponse = $this->goLogClient->optimizeRoutes(
            shipmentIds: $shipmentIds,
            workScheduleIds: $workScheduleIds,
            routePriority: GoLogRoutePriority::TEMPO,
        );

        $skippedShipmentIds = $apiResponse->skippedShipmentIds();
        $plannedShipmentIds = array_values(array_diff($shipmentIds, $skippedShipmentIds));

        if ($plannedShipmentIds !== []) {
            GoLogShipment::query()
                ->whereIn('golog_shipment_id', $plannedShipmentIds)
                ->update(['status' => GoLogShipmentStatus::AGUARDANDO_INICIO]);
        }

        return new OptimizeRoutesResultDTO(
            shipmentsOptimized: count($plannedShipmentIds),
            routesCount: $apiResponse->routesCount,
            workSchedulesUsed: count($workScheduleIds),
            plannedShipmentIds: $plannedShipmentIds,
            skippedShipmentIds: $skippedShipmentIds,
            transportIds: $apiResponse->transportIds,
            workScheduleIds: $workScheduleIds,
        );
    }
}
