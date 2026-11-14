<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Cultiva\Integrations\GoLog\Jobs\ProcessGoLogWebhookJob;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class ReceiveGoLogWebhookAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(
        string $deliveryId,
        GoLogWebhookEventType $eventType,
        array $payload,
    ): GoLogWebhookEvent {
        $lock = Cache::lock("golog:webhook:{$deliveryId}", 10);

        if (! $lock->get()) {
            $existing = GoLogWebhookEvent::where('delivery_id', $deliveryId)->first();

            if ($existing instanceof GoLogWebhookEvent) {
                return $existing;
            }
        }

        try {
            $existing = GoLogWebhookEvent::where('delivery_id', $deliveryId)->first();

            if ($existing instanceof GoLogWebhookEvent) {
                return $existing;
            }

            /** @var array<string, mixed> $data */
            $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
            $occurredAt = is_string($data['occurredAt'] ?? null)
                ? CarbonImmutable::parse($data['occurredAt'])
                : null;

            $webhookEvent = DB::transaction(function () use ($deliveryId, $eventType, $data, $occurredAt, $payload): GoLogWebhookEvent {
                return GoLogWebhookEvent::create([
                    'delivery_id' => $deliveryId,
                    'event' => $eventType,
                    'golog_shipment_id' => is_string($data['shipmentId'] ?? null) ? $data['shipmentId'] : null,
                    'occurrence_id' => is_string($data['occurrenceId'] ?? null) ? $data['occurrenceId'] : null,
                    'occurrence_type' => is_string($data['occurrenceType'] ?? null) ? $data['occurrenceType'] : null,
                    'description' => is_string($data['description'] ?? null) ? $data['description'] : null,
                    'occurred_at' => $occurredAt,
                    'payload' => $payload,
                ]);
            });

            ProcessGoLogWebhookJob::dispatch($webhookEvent->id);

            return $webhookEvent;
        } finally {
            $lock->release();
        }
    }
}
