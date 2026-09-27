<?php

namespace Cultiva\Models\DeliveryOrder;

use Carbon\CarbonImmutable;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Cultiva\Models\Purchase\Purchase;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $purchase_id
 * @property-read ?int $trip_id
 * @property-read ?int $pickup_stop_id
 * @property-read ?int $dropoff_stop_id
 * @property-read int $pickup_address_id
 * @property-read int $dropoff_address_id
 * @property-read int $quantity
 * @property-read DeliveryOrderStatus $status
 * @property-read ?CarbonImmutable $picked_up_at
 * @property-read ?CarbonImmutable $delivered_at
 * @property-read ?CarbonImmutable $failed_at
 * @property-read ?CarbonImmutable $cancelled_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read Purchase $purchase
 * @property-read ?DeliveryTrip $trip
 * @property-read ?DeliveryTripStop $pickupStop
 * @property-read ?DeliveryTripStop $dropoffStop
 * @property-read DeliveryAddress $pickupAddress
 * @property-read DeliveryAddress $dropoffAddress
 */
class DeliveryOrder extends Model
{
    /** @use HasFactory<DeliveryOrderFactory> */
    use HasFactory;

    protected $table = 'delivery_orders';

    protected $fillable = [
        'purchase_id',
        'trip_id',
        'pickup_stop_id',
        'dropoff_stop_id',
        'pickup_address_id',
        'dropoff_address_id',
        'quantity',
        'status',
        'picked_up_at',
        'delivered_at',
        'failed_at',
        'cancelled_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'purchase_id' => 'integer',
            'trip_id' => 'integer',
            'pickup_stop_id' => 'integer',
            'dropoff_stop_id' => 'integer',
            'pickup_address_id' => 'integer',
            'dropoff_address_id' => 'integer',
            'quantity' => 'integer',
            'status' => DeliveryOrderStatus::class,
            'picked_up_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * @return BelongsTo<DeliveryTrip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(DeliveryTrip::class, 'trip_id');
    }

    /**
     * @return BelongsTo<DeliveryTripStop, $this>
     */
    public function pickupStop(): BelongsTo
    {
        return $this->belongsTo(DeliveryTripStop::class, 'pickup_stop_id');
    }

    /**
     * @return BelongsTo<DeliveryTripStop, $this>
     */
    public function dropoffStop(): BelongsTo
    {
        return $this->belongsTo(DeliveryTripStop::class, 'dropoff_stop_id');
    }

    /**
     * @return BelongsTo<DeliveryAddress, $this>
     */
    public function pickupAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class, 'pickup_address_id');
    }

    /**
     * @return BelongsTo<DeliveryAddress, $this>
     */
    public function dropoffAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class, 'dropoff_address_id');
    }
}
