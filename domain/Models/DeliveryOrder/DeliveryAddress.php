<?php

namespace Cultiva\Models\DeliveryOrder;

use Carbon\CarbonImmutable;
use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Database\Factories\DeliveryAddressFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $zip
 * @property-read string $street
 * @property-read string $number
 * @property-read ?string $complement
 * @property-read ?string $reference_point
 * @property-read string $neighborhood
 * @property-read string $city
 * @property-read string $state
 * @property-read mixed $coordinate
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read Collection<int, DeliveryOrder> $pickupOrders
 * @property-read Collection<int, DeliveryOrder> $dropoffOrders
 * @property-read Collection<int, DeliveryTripStop> $tripStops
 */
class DeliveryAddress extends Model
{
    /** @use HasFactory<DeliveryAddressFactory> */
    use HasFactory;

    protected $table = 'delivery_addresses';

    protected $fillable = [
        'zip',
        'street',
        'number',
        'complement',
        'reference_point',
        'neighborhood',
        'city',
        'state',
        'coordinate',
    ];

    /**
     * @return HasMany<DeliveryOrder, $this>
     */
    public function pickupOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'pickup_address_id');
    }

    /**
     * @return HasMany<DeliveryOrder, $this>
     */
    public function dropoffOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'dropoff_address_id');
    }

    /**
     * @return HasMany<DeliveryTripStop, $this>
     */
    public function tripStops(): HasMany
    {
        return $this->hasMany(DeliveryTripStop::class, 'delivery_address_id');
    }
}
