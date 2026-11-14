<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Actions\ReconcileGoLogShipmentsAction;
use Cultiva\Integrations\GoLog\Actions\SyncGoLogShipmentStatusAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogShipmentStatusResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class ReconcileGoLogShipmentsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_reconcile_active_local_shipments_with_remote_golog_statuses(): void
    {
        // Arrange
        $order1 = DeliveryOrderFactory::new()->create(['status' => DeliveryOrderStatus::PENDING]);
        $order2 = DeliveryOrderFactory::new()->create(['status' => DeliveryOrderStatus::DELIVERED]);

        $activeShipment = GoLogShipment::create([
            'delivery_order_id' => $order1->id,
            'golog_shipment_id' => 'active-ship-1',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        // Already finalized locally: should not be queried remotely
        GoLogShipment::create([
            'delivery_order_id' => $order2->id,
            'golog_shipment_id' => 'done-ship-2',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::FINALIZADO,
        ]);

        $client = Mockery::mock(GoLogClient::class);
        $client->expects('getShipment')
            ->once()
            ->with('active-ship-1')
            ->andReturn(new GoLogShipmentStatusResponseDTO(
                id: 'active-ship-1',
                typeOperation: GoLogOperationType::ENTREGA,
                status: GoLogShipmentStatus::FINALIZADO,
            ));

        $sut = new ReconcileGoLogShipmentsAction($client, new SyncGoLogShipmentStatusAction);

        // Action
        $updatedCount = $sut->execute();

        // Assert
        $this->assertSame(1, $updatedCount);
        $this->assertSame(GoLogShipmentStatus::FINALIZADO, $activeShipment->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::DELIVERED, $order1->fresh()->status);
        $this->assertNotNull($order1->fresh()->delivered_at);
    }
}
