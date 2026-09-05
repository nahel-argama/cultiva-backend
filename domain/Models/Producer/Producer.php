<?php

namespace Cultiva\Models\Producer;

use Carbon\CarbonImmutable;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\User\User;
use Database\Factories\ProducerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read bool $is_company
 * @property-read string $document_number
 * @property-read string $trade_name
 * @property-read ?string $legal_name
 * @property-read string $phone
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?User $user
 * @property-read ?Address $address
 */
class Producer extends Model
{
    /** @use HasFactory<ProducerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'is_company',
        'document_number',
        'trade_name',
        'legal_name',
        'phone',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_company' => 'boolean',
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

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
