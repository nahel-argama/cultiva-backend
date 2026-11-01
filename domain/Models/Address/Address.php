<?php

namespace Cultiva\Models\Address;

use Carbon\CarbonImmutable;
use Cultiva\Models\Company\Company;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $company_id
 * @property-read string $zip
 * @property-read string $street
 * @property-read string $number
 * @property-read ?string $complement
 * @property-read ?string $reference_point
 * @property-read string $neighborhood
 * @property-read string $city
 * @property-read string $state
 * @property-read string $coordinate
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read Company $company
 */
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
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
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
