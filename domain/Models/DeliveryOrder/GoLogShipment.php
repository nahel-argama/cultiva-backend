<?php

declare(strict_types=1);

namespace Cultiva\Models\DeliveryOrder;

use Carbon\CarbonImmutable;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $delivery_order_id
 * @property-read string $golog_shipment_id
 * @property-read GoLogShipmentStatus $status
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?DeliveryOrder $deliveryOrder
 */
class GoLogShipment extends Model
{
    protected $table = 'golog_shipments';

    protected $fillable = [
        'delivery_order_id',
        'golog_shipment_id',
        'status',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'delivery_order_id' => 'integer',
            'status' => GoLogShipmentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<DeliveryOrder, $this>
     */
    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }
}
