<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;

class ReconcileGoLogShipmentsAction
{
    public function __construct(
        private readonly GoLogClient $client,
        private readonly SyncGoLogShipmentStatusAction $syncStatusAction,
    ) {}

    public function execute(): int
    {
        $activeShipments = GoLogShipment::with('deliveryOrder')
            ->whereIn('status', [
                GoLogShipmentStatus::PENDENTE,
                GoLogShipmentStatus::AGUARDANDO_INICIO,
                GoLogShipmentStatus::INICIADO,
            ])
            ->get();

        $updatedCount = 0;

        foreach ($activeShipments as $shipment) {
            $remote = $this->client->getShipment($shipment->golog_shipment_id);

            if ($this->syncStatusAction->execute($shipment, $remote->status, $remote->typeOperation)) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }
}
