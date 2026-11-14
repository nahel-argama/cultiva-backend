<?php

declare(strict_types=1);

namespace Cultiva\Models\DeliveryOrder;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Enums\GoLogWebhookEventType;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read int $id
 * @property-read string $delivery_id
 * @property-read GoLogWebhookEventType $event
 * @property-read ?string $golog_shipment_id
 * @property-read ?string $occurrence_id
 * @property-read ?string $occurrence_type
 * @property-read ?string $description
 * @property-read ?CarbonImmutable $occurred_at
 * @property-read array<string, mixed> $payload
 * @property-read ?CarbonImmutable $processed_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 */
class GoLogWebhookEvent extends Model
{
    protected $table = 'golog_webhook_events';

    protected $fillable = [
        'delivery_id',
        'event',
        'golog_shipment_id',
        'occurrence_id',
        'occurrence_type',
        'description',
        'occurred_at',
        'payload',
        'processed_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'event' => GoLogWebhookEventType::class,
            'occurred_at' => 'immutable_datetime',
            'payload' => 'array',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
