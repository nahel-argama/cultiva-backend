<?php

namespace Cultiva\Models\DeliveryTrip;

use Carbon\CarbonImmutable;
use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopType;
use Database\Factories\DeliveryTripStopFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read int $id
 * @property-read int $trip_id
 * @property-read int $delivery_address_id
 * @property-read int $sequence
 * @property-read DeliveryTripStopType $stop_type
 * @property-read DeliveryTripStopStatus $status
 * @property-read ?CarbonImmutable $arrived_at
 * @property-read ?CarbonImmutable $completed_at
 * @property-read ?string $notes
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read DeliveryTrip $trip
 * @property-read DeliveryAddress $deliveryAddress
 * @property-read Collection<int, DeliveryOrder> $pickupOrders
 * @property-read Collection<int, DeliveryOrder> $dropoffOrders
 */
class DeliveryTripStop extends Model
{
    /** @use HasFactory<DeliveryTripStopFactory> */
    use HasFactory;

    protected $table = 'delivery_trip_stops';

    protected $fillable = [
        'trip_id',
        'delivery_address_id',
        'sequence',
        'stop_type',
        'status',
        'arrived_at',
        'completed_at',
        'notes',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'trip_id' => 'integer',
            'delivery_address_id' => 'integer',
            'sequence' => 'integer',
            'stop_type' => DeliveryTripStopType::class,
            'status' => DeliveryTripStopStatus::class,
            'arrived_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<DeliveryTrip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(DeliveryTrip::class, 'trip_id');
    }

    /**
     * @return BelongsTo<DeliveryAddress, $this>
     */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(DeliveryAddress::class, 'delivery_address_id');
    }

    /**
     * @return HasMany<DeliveryOrder, $this>
     */
    public function pickupOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'pickup_stop_id');
    }

    /**
     * @return HasMany<DeliveryOrder, $this>
     */
    public function dropoffOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'dropoff_stop_id');
    }
}
