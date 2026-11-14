<?php

declare(strict_types=1);

namespace Tests\Feature\domain\Integrations\GoLog\Http\Controllers\GoLogWebhookController;

use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Cultiva\Integrations\GoLog\Jobs\ProcessGoLogWebhookJob;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class HandleWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const string WEBHOOK_SECRET = 'whsec_test_secret_123456';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.golog.webhook_secret' => self::WEBHOOK_SECRET,
        ]);
    }

    public function test_should_return_401_when_signature_is_invalid(): void
    {
        // Arrange
        Queue::fake();

        $body = json_encode([
            'event' => 'SHIPMENT_PLANNED',
            'timestamp' => '2026-11-11T14:32:00Z',
            'companyId' => 'company-uuid-1',
            'data' => [
                'shipmentId' => 'shipment-uuid-1',
                'typeOperation' => 'ENTREGA',
                'status' => 'AGUARDANDO_INICIO',
            ],
        ], JSON_THROW_ON_ERROR);

        // Action
        $response = $this->call(
            'POST',
            '/v1/golog/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_GOLOG_EVENT' => 'SHIPMENT_PLANNED',
                'HTTP_X_GOLOG_DELIVERY' => '11111111-1111-4111-8111-111111111111',
                'HTTP_X_GOLOG_TIMESTAMP' => '2026-11-11T14:32:00Z',
                'HTTP_X_GOLOG_SIGNATURE' => 'sha256=invalidsignature',
            ],
            $body,
        );

        // Assert
        $response->assertUnauthorized();
        $this->assertDatabaseCount('golog_webhook_events', 0);
        Queue::assertNothingPushed();
    }

    public function test_should_return_401_when_webhook_secret_is_not_configured(): void
    {
        // Arrange
        Queue::fake();
        config(['services.golog.webhook_secret' => null]);

        $body = json_encode([
            'event' => 'TEST_PING',
            'timestamp' => '2026-11-11T14:32:00Z',
            'companyId' => 'company-uuid-1',
            'data' => [],
        ], JSON_THROW_ON_ERROR);

        // Action
        $response = $this->postWebhook($body, 'TEST_PING', '11111111-1111-4111-8111-111111111112', 'sha256=anything');

        // Assert
        $response->assertUnauthorized();
        $this->assertDatabaseCount('golog_webhook_events', 0);
        Queue::assertNothingPushed();
    }

    public function test_should_persist_webhook_event_and_queue_job_when_signature_is_valid(): void
    {
        // Arrange
        Queue::fake();

        $deliveryId = '22222222-2222-4222-8222-222222222222';
        $payload = [
            'event' => 'SHIPMENT_OCCURRENCE',
            'timestamp' => '2026-11-11T15:10:00Z',
            'companyId' => 'company-uuid-1',
            'data' => [
                'shipmentId' => 'shipment-uuid-42',
                'occurrenceId' => 'occ-uuid-1',
                'occurrenceType' => 'DESTINATARIO_AUSENTE',
                'description' => 'Cliente ausente na portaria',
                'occurredAt' => '2026-11-11T15:09:30Z',
            ],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, self::WEBHOOK_SECRET);

        // Action
        $response = $this->postWebhook($body, 'SHIPMENT_OCCURRENCE', $deliveryId, $signature);

        // Assert
        $response->assertOk()
            ->assertJson(['status' => 'accepted']);

        $this->assertDatabaseHas('golog_webhook_events', [
            'delivery_id' => $deliveryId,
            'event' => GoLogWebhookEventType::SHIPMENT_OCCURRENCE->value,
            'golog_shipment_id' => 'shipment-uuid-42',
            'occurrence_id' => 'occ-uuid-1',
            'occurrence_type' => 'DESTINATARIO_AUSENTE',
            'description' => 'Cliente ausente na portaria',
        ]);

        $storedEvent = GoLogWebhookEvent::where('delivery_id', $deliveryId)->firstOrFail();

        Queue::assertPushed(ProcessGoLogWebhookJob::class, function (ProcessGoLogWebhookJob $job) use ($storedEvent): bool {
            return $job->webhookEventId === $storedEvent->id;
        });
    }

    public function test_should_be_idempotent_when_same_delivery_id_is_received_twice(): void
    {
        // Arrange
        Queue::fake();

        $deliveryId = '33333333-3333-4333-8333-333333333333';
        $payload = [
            'event' => 'TEST_PING',
            'timestamp' => '2026-11-11T15:10:00Z',
            'companyId' => 'company-uuid-1',
            'data' => ['message' => 'ping'],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $body, self::WEBHOOK_SECRET);

        // Action
        $firstResponse = $this->postWebhook($body, 'TEST_PING', $deliveryId, $signature);
        $secondResponse = $this->postWebhook($body, 'TEST_PING', $deliveryId, $signature);

        // Assert
        $firstResponse->assertOk();
        $secondResponse->assertOk();
        $this->assertDatabaseCount('golog_webhook_events', 1);
        Queue::assertPushed(ProcessGoLogWebhookJob::class, 1);
    }

    private function postWebhook(string $rawBody, string $event, string $deliveryId, string $signature)
    {
        return $this->call(
            'POST',
            '/v1/golog/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_GOLOG_EVENT' => $event,
                'HTTP_X_GOLOG_DELIVERY' => $deliveryId,
                'HTTP_X_GOLOG_TIMESTAMP' => '2026-11-11T15:10:00Z',
                'HTTP_X_GOLOG_SIGNATURE' => $signature,
            ],
            $rawBody,
        );
    }
}
