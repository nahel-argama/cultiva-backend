<?php

namespace Cultiva\Models\Address;

use Carbon\CarbonImmutable;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read int $id
 * @property-read string $addressable_type
 * @property-read int $addressable_id
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
 * @property-read ?Model $addressable
 */
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'addressable_type',
        'addressable_id',
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
     * @return MorphTo<Model, $this>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}

