<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Models\DeliveryOrder;

use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoLogShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_golog_shipment_with_relations_and_casted_status_and_type(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create();

        // Action
        $pickupShipment = GoLogShipment::query()->create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's1111111-2222-3333-4444-555555555555',
            'type' => GoLogOperationType::COLETA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        $dropoffShipment = GoLogShipment::query()->create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's6666666-7777-8888-9999-000000000000',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        // Assert
        $this->assertInstanceOf(GoLogShipment::class, $pickupShipment);
        $this->assertSame($order->id, $pickupShipment->delivery_order_id);
        $this->assertSame('s1111111-2222-3333-4444-555555555555', $pickupShipment->golog_shipment_id);
        $this->assertSame(GoLogOperationType::COLETA, $pickupShipment->type);
        $this->assertSame(GoLogShipmentStatus::PENDENTE, $pickupShipment->status);
        $this->assertInstanceOf(DeliveryOrder::class, $pickupShipment->deliveryOrder);
        $this->assertSame($order->id, $pickupShipment->deliveryOrder->id);

        $this->assertSame(GoLogOperationType::ENTREGA, $dropoffShipment->type);

        // Assert relation from DeliveryOrder
        $order->refresh();
        $this->assertCount(2, $order->gologShipments);
        $this->assertSame($pickupShipment->id, $order->pickupShipment?->id);
        $this->assertSame($dropoffShipment->id, $order->dropoffShipment?->id);

        $this->assertDatabaseHas('golog_shipments', [
            'id' => $pickupShipment->id,
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's1111111-2222-3333-4444-555555555555',
            'type' => 'COLETA',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'id' => $dropoffShipment->id,
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's6666666-7777-8888-9999-000000000000',
            'type' => 'ENTREGA',
            'status' => 'PENDENTE',
        ]);
    }
}
