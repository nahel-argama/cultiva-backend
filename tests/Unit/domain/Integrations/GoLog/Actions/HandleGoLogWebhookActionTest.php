<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Actions\HandleGoLogWebhookAction;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HandleGoLogWebhookActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_mark_test_ping_and_occurrence_events_as_processed(): void
    {
        // Arrange
        $pingEvent = GoLogWebhookEvent::create([
            'delivery_id' => '11111111-1111-4111-8111-111111111111',
            'event' => GoLogWebhookEventType::TEST_PING,
            'payload' => ['event' => 'TEST_PING', 'data' => []],
        ]);

        $occurrenceEvent = GoLogWebhookEvent::create([
            'delivery_id' => '22222222-2222-4222-8222-222222222222',
            'event' => GoLogWebhookEventType::SHIPMENT_OCCURRENCE,
            'golog_shipment_id' => 'shipment-uuid-missing',
            'occurrence_id' => 'occ-1',
            'occurrence_type' => 'DESTINATARIO_AUSENTE',
            'description' => 'Portao fechado',
            'occurred_at' => CarbonImmutable::parse('2026-11-11T15:00:00Z'),
            'payload' => ['event' => 'SHIPMENT_OCCURRENCE', 'data' => ['shipmentId' => 'shipment-uuid-missing']],
        ]);

        $sut = new HandleGoLogWebhookAction;

        // Action
        $sut->execute($pingEvent);
        $sut->execute($occurrenceEvent);

        // Assert
        $pingEvent->refresh();
        $occurrenceEvent->refresh();

        $this->assertNotNull($pingEvent->processed_at);
        $this->assertNotNull($occurrenceEvent->processed_at);
    }

    public function test_should_handle_shipment_created_planned_and_unplanned_transitions(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create([
            'status' => DeliveryOrderStatus::PENDING,
        ]);

        $coleta = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'coleta-uuid-1',
            'type' => GoLogOperationType::COLETA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        $createdEvent = GoLogWebhookEvent::create([
            'delivery_id' => '33333333-3333-4333-8333-333333333331',
            'event' => GoLogWebhookEventType::SHIPMENT_CREATED,
            'golog_shipment_id' => 'coleta-uuid-1',
            'payload' => [
                'event' => 'SHIPMENT_CREATED',
                'timestamp' => '2026-11-11T12:00:00Z',
                'data' => [
                    'shipmentId' => 'coleta-uuid-1',
                    'typeOperation' => 'COLETA',
                    'status' => 'PENDENTE',
                ],
            ],
        ]);

        $plannedEvent = GoLogWebhookEvent::create([
            'delivery_id' => '33333333-3333-4333-8333-333333333332',
            'event' => GoLogWebhookEventType::SHIPMENT_PLANNED,
            'golog_shipment_id' => 'coleta-uuid-1',
            'payload' => [
                'event' => 'SHIPMENT_PLANNED',
                'timestamp' => '2026-11-11T13:00:00Z',
                'data' => [
                    'shipmentId' => 'coleta-uuid-1',
                    'typeOperation' => 'COLETA',
                    'status' => 'AGUARDANDO_INICIO',
                ],
            ],
        ]);

        $unplannedEvent = GoLogWebhookEvent::create([
            'delivery_id' => '33333333-3333-4333-8333-333333333333',
            'event' => GoLogWebhookEventType::SHIPMENT_UNPLANNED,
            'golog_shipment_id' => 'coleta-uuid-1',
            'payload' => [
                'event' => 'SHIPMENT_UNPLANNED',
                'timestamp' => '2026-11-11T13:30:00Z',
                'data' => [
                    'shipmentId' => 'coleta-uuid-1',
                    'typeOperation' => 'COLETA',
                    'status' => 'PENDENTE',
                ],
            ],
        ]);

        $sut = new HandleGoLogWebhookAction;

        // Action & Assert - CREATED
        $sut->execute($createdEvent);
        $this->assertSame(GoLogShipmentStatus::PENDENTE, $coleta->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::PENDING, $order->fresh()->status);
        $this->assertNotNull($createdEvent->fresh()->processed_at);

        // Action & Assert - PLANNED
        $sut->execute($plannedEvent);
        $this->assertSame(GoLogShipmentStatus::AGUARDANDO_INICIO, $coleta->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::ASSIGNED, $order->fresh()->status);
        $this->assertNotNull($plannedEvent->fresh()->processed_at);

        // Action & Assert - UNPLANNED
        $sut->execute($unplannedEvent);
        $this->assertSame(GoLogShipmentStatus::PENDENTE, $coleta->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::PENDING, $order->fresh()->status);
        $this->assertNotNull($unplannedEvent->fresh()->processed_at);
    }

    public function test_should_transition_delivery_order_through_coleta_started_picked_up_and_entrega_delivered(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create([
            'status' => DeliveryOrderStatus::ASSIGNED,
        ]);

        $coleta = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'coleta-uuid-2',
            'type' => GoLogOperationType::COLETA,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);

        $entrega = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'entrega-uuid-2',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);

        $coletaStarted = GoLogWebhookEvent::create([
            'delivery_id' => '44444444-4444-4444-8444-444444444441',
            'event' => GoLogWebhookEventType::SHIPMENT_STARTED,
            'golog_shipment_id' => 'coleta-uuid-2',
            'payload' => [
                'event' => 'SHIPMENT_STARTED',
                'timestamp' => '2026-11-11T14:00:00Z',
                'data' => [
                    'shipmentId' => 'coleta-uuid-2',
                    'typeOperation' => 'COLETA',
                    'status' => 'INICIADO',
                ],
            ],
        ]);

        $coletaDelivered = GoLogWebhookEvent::create([
            'delivery_id' => '44444444-4444-4444-8444-444444444442',
            'event' => GoLogWebhookEventType::SHIPMENT_DELIVERED,
            'golog_shipment_id' => 'coleta-uuid-2',
            'payload' => [
                'event' => 'SHIPMENT_DELIVERED',
                'timestamp' => '2026-11-11T15:00:00Z',
                'data' => [
                    'shipmentId' => 'coleta-uuid-2',
                    'typeOperation' => 'COLETA',
                    'status' => 'FINALIZADO',
                ],
            ],
        ]);

        $entregaDelivered = GoLogWebhookEvent::create([
            'delivery_id' => '44444444-4444-4444-8444-444444444443',
            'event' => GoLogWebhookEventType::SHIPMENT_DELIVERED,
            'golog_shipment_id' => 'entrega-uuid-2',
            'payload' => [
                'event' => 'SHIPMENT_DELIVERED',
                'timestamp' => '2026-11-11T17:30:00Z',
                'data' => [
                    'shipmentId' => 'entrega-uuid-2',
                    'typeOperation' => 'ENTREGA',
                    'status' => 'FINALIZADO',
                ],
            ],
        ]);

        $sut = new HandleGoLogWebhookAction;

        // Action & Assert - COLETA STARTED
        $sut->execute($coletaStarted);
        $this->assertSame(GoLogShipmentStatus::INICIADO, $coleta->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::IN_TRANSIT, $order->fresh()->status);

        // Action & Assert - COLETA DELIVERED (pickup complete)
        $sut->execute($coletaDelivered);
        $freshAfterPickup = $order->fresh();
        $this->assertSame(GoLogShipmentStatus::FINALIZADO, $coleta->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::IN_TRANSIT, $freshAfterPickup->status);
        $this->assertSame('2026-11-11T15:00:00+00:00', $freshAfterPickup->picked_up_at?->toIso8601String());
        $this->assertNull($freshAfterPickup->delivered_at);

        // Action & Assert - ENTREGA DELIVERED (dropoff complete)
        $sut->execute($entregaDelivered);
        $freshAfterDropoff = $order->fresh();
        $this->assertSame(GoLogShipmentStatus::FINALIZADO, $entrega->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::DELIVERED, $freshAfterDropoff->status);
        $this->assertSame('2026-11-11T17:30:00+00:00', $freshAfterDropoff->delivered_at?->toIso8601String());
    }
}
