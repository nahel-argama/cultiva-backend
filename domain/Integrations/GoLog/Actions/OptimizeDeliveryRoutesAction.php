<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Illuminate\Support\Facades\DB;

class OptimizeDeliveryRoutesAction
{
    private readonly ResolveAvailableWorkSchedulesAction $resolveWorkSchedules;

    public function __construct(
        private readonly GoLogClient $goLogClient,
        private readonly SyncGoLogShipmentStatusAction $syncStatusAction = new SyncGoLogShipmentStatusAction,
        ?ResolveAvailableWorkSchedulesAction $resolveWorkSchedules = null,
    ) {
        $this->resolveWorkSchedules = $resolveWorkSchedules ?? new ResolveAvailableWorkSchedulesAction($this->goLogClient);
    }

    public function execute(): OptimizeRoutesResultDTO
    {
        $shipmentIds = $this->findPendingShipmentIds();

        if ($shipmentIds === []) {
            return $this->emptyResult();
        }

        $workScheduleIds = $this->resolveWorkSchedules->execute()->workScheduleIds;

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

        DB::transaction(function () use ($plannedShipmentIds): void {
            $shipments = GoLogShipment::with('deliveryOrder')
                ->whereIn('golog_shipment_id', $plannedShipmentIds)
                ->orderBy('id')
                ->get();

            foreach ($shipments as $shipment) {
                $this->syncStatusAction->execute($shipment, GoLogShipmentStatus::AGUARDANDO_INICIO);
            }
        });
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
