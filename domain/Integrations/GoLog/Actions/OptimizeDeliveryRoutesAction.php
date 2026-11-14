<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\DeliveryOrder\GoLogShipment;

class OptimizeDeliveryRoutesAction
{
    private const string SCHEDULE_TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(): OptimizeRoutesResultDTO
    {
        $shipmentIds = $this->findPendingShipmentIds();

        if ($shipmentIds === []) {
            return $this->emptyResult();
        }

        $provisionedDriverIds = $this->findProvisionedDriverIds();

        if ($provisionedDriverIds === []) {
            throw new CultivaException(
                422,
                'No provisioned GoLog drivers available for route optimization.'
            );
        }

        $scheduleDate = CarbonImmutable::now(self::SCHEDULE_TIMEZONE)->nextWeekday()->toDateString();
        $workScheduleIds = $this->resolveActiveWorkScheduleIds($provisionedDriverIds, $scheduleDate);

        if ($workScheduleIds === []) {
            throw new CultivaException(
                422,
                "No active GoLog work schedules found for date [{$scheduleDate}]."
            );
        }

        $apiResponse = $this->goLogClient->optimizeRoutes(
            shipmentIds: $shipmentIds,
            workScheduleIds: $workScheduleIds,
            routePriority: GoLogRoutePriority::TEMPO,
        );

        $skippedShipmentIds = $apiResponse->skippedShipmentIds();
        $plannedShipmentIds = $this->resolvePlannedShipmentIds($shipmentIds, $skippedShipmentIds);

        $this->markShipmentsAsAwaitingStart($plannedShipmentIds);

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

    /**
     * @return array<int, string>
     */
    private function findPendingShipmentIds(): array
    {
        /** @var array<int, string> */
        return GoLogShipment::query()
            ->where('status', GoLogShipmentStatus::PENDENTE)
            ->orderBy('id')
            ->pluck('golog_shipment_id')
            ->all();
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

    /**
     * @param array<int, string> $provisionedDriverIds
     * @return array<int, string>
     */
    private function resolveActiveWorkScheduleIds(array $provisionedDriverIds, string $scheduleDate): array
    {
        $workScheduleIds = [];

        foreach ($this->goLogClient->listActiveWorkSchedules() as $schedule) {
            if ($schedule->scheduleDate !== $scheduleDate) {
                continue;
            }

            if (! in_array($schedule->driverId, $provisionedDriverIds, true)) {
                continue;
            }

            $workScheduleIds[] = $schedule->id;
        }

        return $workScheduleIds;
    }

    /**
     * @param array<int, string> $shipmentIds
     * @param array<int, string> $skippedShipmentIds
     * @return array<int, string>
     */
    private function resolvePlannedShipmentIds(array $shipmentIds, array $skippedShipmentIds): array
    {
        $plannedShipmentIds = [];

        foreach ($shipmentIds as $shipmentId) {
            if (in_array($shipmentId, $skippedShipmentIds, true)) {
                continue;
            }

            $plannedShipmentIds[] = $shipmentId;
        }

        return $plannedShipmentIds;
    }

    /**
     * @param array<int, string> $plannedShipmentIds
     */
    private function markShipmentsAsAwaitingStart(array $plannedShipmentIds): void
    {
        if ($plannedShipmentIds === []) {
            return;
        }

        GoLogShipment::query()
            ->whereIn('golog_shipment_id', $plannedShipmentIds)
            ->update(['status' => GoLogShipmentStatus::AGUARDANDO_INICIO]);
    }

    private function emptyResult(): OptimizeRoutesResultDTO
    {
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
}
