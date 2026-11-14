<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Models\DeliveryOrder;

use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoLogWebhookEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_persist_golog_webhook_event_with_unique_delivery_id_and_casted_fields(): void
    {
        // Arrange
        $deliveryId = '11111111-2222-3333-4444-555555555555';
        $payload = [
            'event' => 'SHIPMENT_OCCURRENCE',
            'timestamp' => '2026-12-01T10:02:11Z',
            'data' => [
                'shipmentId' => '99999999-2222-3333-4444-555555555555',
                'occurrenceId' => '88888888-2222-3333-4444-555555555555',
                'occurrenceType' => 'ATRASO',
                'description' => 'Trânsito na BR-470',
                'occurredAt' => '2026-12-01T10:00:00Z',
            ],
        ];

        // Action
        $event = GoLogWebhookEvent::query()->create([
            'delivery_id' => $deliveryId,
            'event' => GoLogWebhookEventType::SHIPMENT_OCCURRENCE,
            'golog_shipment_id' => '99999999-2222-3333-4444-555555555555',
            'occurrence_id' => '88888888-2222-3333-4444-555555555555',
            'occurrence_type' => 'ATRASO',
            'description' => 'Trânsito na BR-470',
            'occurred_at' => '2026-12-01T10:00:00Z',
            'payload' => $payload,
        ]);

        // Assert
        $this->assertSame($deliveryId, $event->delivery_id);
        $this->assertSame(GoLogWebhookEventType::SHIPMENT_OCCURRENCE, $event->event);
        $this->assertSame($payload, $event->payload);
        $this->assertNull($event->processed_at);

        $this->expectException(UniqueConstraintViolationException::class);
        GoLogWebhookEvent::query()->create([
            'delivery_id' => $deliveryId,
            'event' => GoLogWebhookEventType::SHIPMENT_OCCURRENCE,
            'payload' => $payload,
        ]);
    }
}
