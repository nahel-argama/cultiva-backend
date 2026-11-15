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
        $resolvedTimestamp = $eventTimestamp ?? CarbonImmutable::now();

        return DB::transaction(function () use ($shipment, $nextStatus, $operationType, $resolvedTimestamp): bool {
            $lockedShipment = GoLogShipment::query()
                ->whereKey($shipment->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedShipment instanceof GoLogShipment) {
                return false;
            }

            $shipmentStatusChanged = $lockedShipment->status !== $nextStatus;
            $resolvedOperation = $operationType ?? $lockedShipment->type;
            $order = $lockedShipment->deliveryOrder()->lockForUpdate()->first();

            if (! $shipmentStatusChanged && ! $this->shouldSyncOrderWhenShipmentUnchanged($order, $nextStatus, $resolvedOperation)) {
                $this->refreshCallerShipment($shipment, $lockedShipment, $order);

                return false;
            }

            if ($shipmentStatusChanged) {
                $lockedShipment->update(['status' => $nextStatus]);
            }

            if ($order instanceof DeliveryOrder) {
                $this->transitionDeliveryOrder($order, $nextStatus, $resolvedOperation, $resolvedTimestamp);
            }

            $this->refreshCallerShipment($shipment, $lockedShipment, $order);

            return true;
        });
    }

    private function refreshCallerShipment(
        GoLogShipment $callerShipment,
        GoLogShipment $lockedShipment,
        ?DeliveryOrder $order,
    ): void {
        $callerShipment->setRawAttributes($lockedShipment->getAttributes(), true);
        $callerShipment->setRelation('deliveryOrder', $order);
    }

    private function shouldSyncOrderWhenShipmentUnchanged(
        ?DeliveryOrder $order,
        GoLogShipmentStatus $status,
        GoLogOperationType $operationType,
    ): bool {
        if (! $order instanceof DeliveryOrder) {
            return false;
        }

        return match ($status) {
            GoLogShipmentStatus::AGUARDANDO_INICIO => $order->status === DeliveryOrderStatus::PENDING,
            GoLogShipmentStatus::INICIADO => in_array($order->status, [
                DeliveryOrderStatus::PENDING,
                DeliveryOrderStatus::ASSIGNED,
            ], true),
            GoLogShipmentStatus::FINALIZADO => $this->isOrderPendingFinalization($order, $operationType),
            GoLogShipmentStatus::CANCELADO => ! in_array($order->status, [
                DeliveryOrderStatus::DELIVERED,
                DeliveryOrderStatus::CANCELLED,
            ], true),
            GoLogShipmentStatus::PENDENTE => false,
        };
    }

    private function isOrderPendingFinalization(DeliveryOrder $order, GoLogOperationType $operationType): bool
    {
        if ($operationType === GoLogOperationType::COLETA) {
            return $order->picked_up_at === null
                || ! in_array($order->status, [DeliveryOrderStatus::IN_TRANSIT, DeliveryOrderStatus::DELIVERED], true);
        }

        return $order->status !== DeliveryOrderStatus::DELIVERED
            || $order->delivered_at === null;
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
