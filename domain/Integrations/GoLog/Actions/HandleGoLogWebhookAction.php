<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Illuminate\Support\Facades\DB;

final readonly class HandleGoLogWebhookAction
{
    public function __construct(
        private SyncGoLogShipmentStatusAction $syncStatusAction = new SyncGoLogShipmentStatusAction,
    ) {}

    public function execute(GoLogWebhookEvent $event): void
    {
        DB::transaction(function () use ($event): void {
            match ($event->event) {
                GoLogWebhookEventType::TEST_PING,
                GoLogWebhookEventType::SHIPMENT_OCCURRENCE => null,
                GoLogWebhookEventType::SHIPMENT_CREATED,
                GoLogWebhookEventType::SHIPMENT_PLANNED,
                GoLogWebhookEventType::SHIPMENT_UNPLANNED,
                GoLogWebhookEventType::SHIPMENT_STARTED,
                GoLogWebhookEventType::SHIPMENT_DELIVERED => $this->applyShipmentEvent($event),
            };

            $event->update(['processed_at' => CarbonImmutable::now()]);
        });
    }

    private function applyShipmentEvent(GoLogWebhookEvent $event): void
    {
        if ($event->golog_shipment_id === null) {
            return;
        }

        $shipment = GoLogShipment::with('deliveryOrder')
            ->where('golog_shipment_id', $event->golog_shipment_id)
            ->first();

        if (! $shipment instanceof GoLogShipment) {
            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = $event->payload;
        /** @var array<string, mixed> $data */
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $nextShipmentStatus = $this->resolveShipmentStatus($event->event, $data['status'] ?? null);

        if ($nextShipmentStatus === null) {
            return;
        }

        $operationType = is_string($data['typeOperation'] ?? null)
            ? GoLogOperationType::tryFrom($data['typeOperation'])
            : null;
        $eventTimestamp = is_string($payload['timestamp'] ?? null)
            ? CarbonImmutable::parse($payload['timestamp'])
            : CarbonImmutable::now();

        $this->syncStatusAction->execute($shipment, $nextShipmentStatus, $operationType, $eventTimestamp);
    }

    private function resolveShipmentStatus(GoLogWebhookEventType $eventType, mixed $rawStatus): ?GoLogShipmentStatus
    {
        if (is_string($rawStatus)) {
            $parsed = GoLogShipmentStatus::tryFrom($rawStatus);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return match ($eventType) {
            GoLogWebhookEventType::SHIPMENT_CREATED,
            GoLogWebhookEventType::SHIPMENT_UNPLANNED => GoLogShipmentStatus::PENDENTE,
            GoLogWebhookEventType::SHIPMENT_PLANNED => GoLogShipmentStatus::AGUARDANDO_INICIO,
            GoLogWebhookEventType::SHIPMENT_STARTED => GoLogShipmentStatus::INICIADO,
            GoLogWebhookEventType::SHIPMENT_DELIVERED => GoLogShipmentStatus::FINALIZADO,
            default => null,
        };
    }
}
