<?php

namespace Cultiva\Models\Retailer;

use Carbon\CarbonImmutable;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Retailer\Enums\BusinessType;
use Database\Factories\RetailerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $company_id
 * @property-read BusinessType $business_type
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Company $company
 */
class Retailer extends Model
{
    /** @use HasFactory<RetailerFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'business_type',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'business_type' => BusinessType::class,
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
