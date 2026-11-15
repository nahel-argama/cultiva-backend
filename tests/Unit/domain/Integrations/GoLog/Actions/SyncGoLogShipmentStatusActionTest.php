<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Actions\SyncGoLogShipmentStatusAction;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyncGoLogShipmentStatusActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_assign_pending_delivery_order_when_shipment_is_already_awaiting_start(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create([
            'status' => DeliveryOrderStatus::PENDING,
        ]);
        $shipment = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'ship-awaiting-1',
            'type' => GoLogOperationType::COLETA,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);
        $sut = new SyncGoLogShipmentStatusAction;

        // Action
        $updated = $sut->execute($shipment, GoLogShipmentStatus::AGUARDANDO_INICIO);

        // Assert
        $this->assertTrue($updated);
        $this->assertSame(DeliveryOrderStatus::ASSIGNED, $order->fresh()->status);
    }

    public function test_should_return_false_when_shipment_and_delivery_order_are_already_synced(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create([
            'status' => DeliveryOrderStatus::ASSIGNED,
        ]);
        $shipment = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'ship-awaiting-2',
            'type' => GoLogOperationType::COLETA,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);
        $sut = new SyncGoLogShipmentStatusAction;

        // Action
        $updated = $sut->execute($shipment, GoLogShipmentStatus::AGUARDANDO_INICIO);

        // Assert
        $this->assertFalse($updated);
        $this->assertSame(DeliveryOrderStatus::ASSIGNED, $order->fresh()->status);
    }

    public function test_should_not_revert_assigned_order_when_sibling_shipment_remains_pending(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create([
            'status' => DeliveryOrderStatus::ASSIGNED,
        ]);
        GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'ship-coleta-1',
            'type' => GoLogOperationType::COLETA,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);
        $entrega = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'ship-entrega-1',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);
        $sut = new SyncGoLogShipmentStatusAction;

        // Action
        $updated = $sut->execute($entrega, GoLogShipmentStatus::PENDENTE);

        // Assert
        $this->assertFalse($updated);
        $this->assertSame(DeliveryOrderStatus::ASSIGNED, $order->fresh()->status);
    }
}
