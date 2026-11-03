<?php

namespace Cultiva\Models\Delivery;

use Carbon\CarbonImmutable;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Vehicle\GoLogVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * @property-read int $id
 * @property-read int $company_id
 * @property-read string $golog_user_id
 * @property-read string $golog_driver_id
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?Company $company
 * @property-read ?GoLogVehicle $vehicle
 */
class GoLogDriver extends Model
{
    protected $table = 'golog_drivers';

    protected $fillable = [
        'company_id',
        'golog_user_id',
        'golog_driver_id',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
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
     * @return HasOne<GoLogVehicle, $this>
     */
    public function vehicle(): HasOne
    {
        return $this->hasOne(GoLogVehicle::class, 'golog_driver_id');
    }
}
