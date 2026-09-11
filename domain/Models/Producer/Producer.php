<?php

namespace Cultiva\Models\Producer;

use Carbon\CarbonImmutable;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Enums\ActivitySegment;
use Database\Factories\ProducerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $company_id
 * @property-read ActivitySegment $activity_segment
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Company $company
 */
class Producer extends Model
{
    /** @use HasFactory<ProducerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'activity_segment',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'activity_segment' => ActivitySegment::class,
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
