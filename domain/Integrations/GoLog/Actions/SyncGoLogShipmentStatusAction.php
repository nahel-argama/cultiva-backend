<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Illuminate\Support\Facades\DB;

final readonly class SyncGoLogShipmentStatusAction
{
    public function execute(
        GoLogShipment $shipment,
        GoLogShipmentStatus $nextStatus,
        ?GoLogOperationType $operationType = null,
        ?CarbonImmutable $eventTimestamp = null,
    ): bool {
        if ($shipment->status === $nextStatus) {
            return false;
        }

        $resolvedOperation = $operationType ?? $shipment->type;
        $resolvedTimestamp = $eventTimestamp ?? CarbonImmutable::now();

        DB::transaction(function () use ($shipment, $nextStatus, $resolvedOperation, $resolvedTimestamp): void {
            $shipment->update(['status' => $nextStatus]);

            $order = $shipment->deliveryOrder;

            if (! $order instanceof DeliveryOrder) {
                return;
            }

            $this->transitionDeliveryOrder($order, $nextStatus, $resolvedOperation, $resolvedTimestamp);
        });

        return true;
    }

    private function transitionDeliveryOrder(
        DeliveryOrder $order,
        GoLogShipmentStatus $nextStatus,
        GoLogOperationType $operationType,
        CarbonImmutable $eventTimestamp,
    ): void {
        match ($nextStatus) {
            GoLogShipmentStatus::AGUARDANDO_INICIO => $this->markAssignedIfPending($order),
            GoLogShipmentStatus::PENDENTE => $this->revertToPendingIfAssigned($order),
            GoLogShipmentStatus::INICIADO => $this->markInTransitIfActive($order),
            GoLogShipmentStatus::FINALIZADO => $this->applyFinalized($order, $operationType, $eventTimestamp),
            GoLogShipmentStatus::CANCELADO => $this->markCancelledIfNotFinal($order, $eventTimestamp),
        };
    }

    private function markAssignedIfPending(DeliveryOrder $order): void
    {
        if ($order->status === DeliveryOrderStatus::PENDING) {
            $order->update(['status' => DeliveryOrderStatus::ASSIGNED]);
        }
    }

    private function revertToPendingIfAssigned(DeliveryOrder $order): void
    {
        if ($order->status === DeliveryOrderStatus::ASSIGNED) {
            $order->update(['status' => DeliveryOrderStatus::PENDING]);
        }
    }

    private function markInTransitIfActive(DeliveryOrder $order): void
    {
        if (in_array($order->status, [DeliveryOrderStatus::PENDING, DeliveryOrderStatus::ASSIGNED], true)) {
            $order->update(['status' => DeliveryOrderStatus::IN_TRANSIT]);
        }
    }

    private function applyFinalized(
        DeliveryOrder $order,
        GoLogOperationType $operationType,
        CarbonImmutable $eventTimestamp,
    ): void {
        if ($operationType === GoLogOperationType::COLETA) {
            $attributes = ['picked_up_at' => $order->picked_up_at ?? $eventTimestamp];

            if ($order->status !== DeliveryOrderStatus::DELIVERED) {
                $attributes['status'] = DeliveryOrderStatus::IN_TRANSIT;
            }

            $order->update($attributes);

            return;
        }

        $order->update([
            'status' => DeliveryOrderStatus::DELIVERED,
            'delivered_at' => $order->delivered_at ?? $eventTimestamp,
        ]);
    }

    private function markCancelledIfNotFinal(DeliveryOrder $order, CarbonImmutable $eventTimestamp): void
    {
        if (! in_array($order->status, [DeliveryOrderStatus::DELIVERED, DeliveryOrderStatus::CANCELLED], true)) {
            $order->update([
                'status' => DeliveryOrderStatus::CANCELLED,
                'cancelled_at' => $order->cancelled_at ?? $eventTimestamp,
            ]);
        }
    }
}
