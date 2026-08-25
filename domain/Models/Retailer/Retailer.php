<?php

namespace Cultiva\Models\Retailer;

use Carbon\CarbonImmutable;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Retailer\Enums\BusinessType;
use Cultiva\Models\User\User;
use Database\Factories\RetailerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string $document_number
 * @property-read string $trade_name
 * @property-read ?string $legal_name
 * @property-read BusinessType $business_type
 * @property-read string $phone
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?User $user
 * @property-read ?Address $address
 */
class Retailer extends Model
{
    /** @use HasFactory<RetailerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'document_number',
        'trade_name',
        'legal_name',
        'business_type',
        'phone',
    ];

    #[Override]
    protected function casts()
    {
        return [
            'business_type' => BusinessType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphOne<Address, $this>
     */
    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
