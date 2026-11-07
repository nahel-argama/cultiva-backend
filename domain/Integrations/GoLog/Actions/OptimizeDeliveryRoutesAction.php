<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Illuminate\Database\Eloquent\Collection;

class OptimizeDeliveryRoutesAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    public function execute(): OptimizeRoutesResultDTO
    {
        /** @var Collection<int, GoLogShipment> $shipments */
        $shipments = GoLogShipment::query()
            ->where('status', GoLogShipmentStatus::PENDENTE)
            ->orderBy('id')
            ->get();

        if ($shipments->isEmpty()) {
            return new OptimizeRoutesResultDTO(
                shipmentsOptimized: 0,
                workSchedulesUsed: 0,
                shipmentIds: [],
                workScheduleIds: [],
                response: '',
            );
        }

        /** @var array<int, string> $workScheduleIds */
        $workScheduleIds = GoLogDriver::query()
            ->whereNotNull('golog_user_id')
            ->whereNotNull('golog_driver_id')
            ->whereNotNull('golog_work_schedule_id')
            ->whereHas('vehicle', fn ($query) => $query
                ->whereNotNull('golog_tractor_id')
                ->whereNotNull('golog_equipament_group_id')
            )
            ->orderBy('id')
            ->pluck('golog_work_schedule_id')
            ->all();

        $this->validateBusinessRules($workScheduleIds);

        /** @var array<int, string> $shipmentIds */
        $shipmentIds = $shipments->pluck('golog_shipment_id')->all();

        $response = $this->goLogClient->optimizeRoutes(
            shipmentIds: $shipmentIds,
            workScheduleIds: $workScheduleIds,
            routePriority: GoLogRoutePriority::TEMPO,
        );

        GoLogShipment::query()
            ->whereIn('id', $shipments->pluck('id'))
            ->update(['status' => GoLogShipmentStatus::AGUARDANDO_INICIO]);

        return new OptimizeRoutesResultDTO(
            shipmentsOptimized: $shipments->count(),
            workSchedulesUsed: count($workScheduleIds),
            shipmentIds: $shipmentIds,
            workScheduleIds: $workScheduleIds,
            response: $response,
        );
    }

    /**
     * @param array<int, string> $workScheduleIds
     */
    private function validateBusinessRules(array $workScheduleIds): void
    {
        if ($workScheduleIds === []) {
            throw new CultivaException(
                422,
                'No provisioned GoLog drivers with active work schedules available for route optimization.'
            );
        }
    }
}
