<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Models\DeliveryOrder;

use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoLogShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_golog_shipment_with_relations_and_casted_status(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create();

        // Action
        $shipment = GoLogShipment::query()->create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's1111111-2222-3333-4444-555555555555',
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        // Assert
        $this->assertInstanceOf(GoLogShipment::class, $shipment);
        $this->assertSame($order->id, $shipment->delivery_order_id);
        $this->assertSame('s1111111-2222-3333-4444-555555555555', $shipment->golog_shipment_id);
        $this->assertSame(GoLogShipmentStatus::PENDENTE, $shipment->status);
        $this->assertInstanceOf(DeliveryOrder::class, $shipment->deliveryOrder);
        $this->assertSame($order->id, $shipment->deliveryOrder->id);

        // Assert relation from DeliveryOrder
        $order->refresh();
        $this->assertInstanceOf(GoLogShipment::class, $order->gologShipment);
        $this->assertSame($shipment->id, $order->gologShipment->id);

        $this->assertDatabaseHas('golog_shipments', [
            'id' => $shipment->id,
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's1111111-2222-3333-4444-555555555555',
            'status' => 'PENDENTE',
        ]);
    }
}
