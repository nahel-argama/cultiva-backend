<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Jobs;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Actions\HandleGoLogWebhookAction;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Cultiva\Integrations\GoLog\Jobs\ProcessGoLogWebhookJob;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProcessGoLogWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_process_webhook_event_and_skip_already_processed_event(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create([
            'status' => DeliveryOrderStatus::PENDING,
        ]);

        $shipment = GoLogShipment::create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 'shipment-job-uuid-1',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        $event = GoLogWebhookEvent::create([
            'delivery_id' => '55555555-5555-4555-8555-555555555555',
            'event' => GoLogWebhookEventType::SHIPMENT_PLANNED,
            'golog_shipment_id' => 'shipment-job-uuid-1',
            'payload' => [
                'event' => 'SHIPMENT_PLANNED',
                'timestamp' => '2026-11-11T16:00:00Z',
                'data' => [
                    'shipmentId' => 'shipment-job-uuid-1',
                    'typeOperation' => 'ENTREGA',
                    'status' => 'AGUARDANDO_INICIO',
                ],
            ],
        ]);

        $sut = new ProcessGoLogWebhookJob($event->id);
        $action = new HandleGoLogWebhookAction;

        // Action
        $sut->handle($action);

        // Assert
        $this->assertSame(GoLogShipmentStatus::AGUARDANDO_INICIO, $shipment->fresh()->status);
        $this->assertSame(DeliveryOrderStatus::ASSIGNED, $order->fresh()->status);
        $firstProcessedAt = $event->fresh()->processed_at;
        $this->assertInstanceOf(CarbonImmutable::class, $firstProcessedAt);

        // Re-running job on already-processed event must be a no-op
        $shipment->update(['status' => GoLogShipmentStatus::INICIADO]);
        $sut->handle($action);

        $this->assertSame(GoLogShipmentStatus::INICIADO, $shipment->fresh()->status);
        $this->assertSame($firstProcessedAt->toIso8601String(), $event->fresh()->processed_at?->toIso8601String());
    }
}
