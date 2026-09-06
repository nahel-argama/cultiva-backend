<?php

namespace Cultiva\Models\Company;

use Carbon\CarbonImmutable;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\User\User;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string $document_number
 * @property-read string $trade_name
 * @property-read ?string $legal_name
 * @property-read string $phone
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?User $user
 * @property-read ?Producer $producer
 * @property-read ?Retailer $retailer
 * @property-read ?Delivery $delivery
 * @property-read ?Address $address
 */
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'document_number',
        'trade_name',
        'legal_name',
        'phone',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<Producer, $this>
     */
    public function producer(): HasOne
    {
        return $this->hasOne(Producer::class);
    }

    /**
     * @return HasOne<Retailer, $this>
     */
    public function retailer(): HasOne
    {
        return $this->hasOne(Retailer::class);
    }

    /**
     * @return HasOne<Delivery, $this>
     */
    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    /**
     * @return MorphOne<Address, $this>
     */
    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
