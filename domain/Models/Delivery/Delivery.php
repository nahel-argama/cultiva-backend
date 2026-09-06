<?php

namespace Cultiva\Models\Delivery;

use Carbon\CarbonImmutable;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\Enums\CnhCategory;
use Cultiva\Models\Vehicle\Vehicle;
use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $company_id
 * @property-read string $cnh_number
 * @property-read CnhCategory $cnh_category
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Company $company
 * @property-read ?Vehicle $vehicle
 * @property-read Collection<int, Vehicle> $vehicles
 */
class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'cnh_number',
        'cnh_category',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'cnh_category' => CnhCategory::class,
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
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasOne<Vehicle, $this>
     */
    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class);
    }
}
