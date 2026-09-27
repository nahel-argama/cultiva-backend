<?php

namespace Cultiva\Models\DeliveryTrip;

use Carbon\CarbonImmutable;
use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Cultiva\Models\Vehicle\Vehicle;
use Database\Factories\DeliveryTripFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read int $id
 * @property-read ?int $delivery_id
 * @property-read ?int $vehicle_id
 * @property-read int $cargo_type_id
 * @property-read DeliveryTripStatus $status
 * @property-read ?CarbonImmutable $started_at
 * @property-read ?CarbonImmutable $completed_at
 * @property-read ?CarbonImmutable $cancelled_at
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?Delivery $delivery
 * @property-read ?Vehicle $vehicle
 * @property-read CargoType $cargoType
 * @property-read Collection<int, DeliveryTripStop> $stops
 * @property-read Collection<int, DeliveryOrder> $orders
 */
class DeliveryTrip extends Model
{
    /** @use HasFactory<DeliveryTripFactory> */
    use HasFactory;

    protected $table = 'delivery_trips';

    protected $fillable = [
        'delivery_id',
        'vehicle_id',
        'cargo_type_id',
        'status',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'delivery_id' => 'integer',
            'vehicle_id' => 'integer',
            'cargo_type_id' => 'integer',
            'status' => DeliveryTripStatus::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<CargoType, $this>
     */
    public function cargoType(): BelongsTo
    {
        return $this->belongsTo(CargoType::class);
    }

    /**
     * @return HasMany<DeliveryTripStop, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(DeliveryTripStop::class, 'trip_id')->orderBy('sequence');
    }

    /**
     * @return HasMany<DeliveryOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'trip_id');
    }
}
