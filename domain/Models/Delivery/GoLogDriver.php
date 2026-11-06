<?php

namespace Cultiva\Models\Delivery;

use Carbon\CarbonImmutable;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Vehicle\GoLogVehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * @property-read int $id
 * @property-read int $company_id
 * @property-read string $cpf
 * @property-read ?string $golog_user_id
 * @property-read ?string $golog_driver_id
 * @property-read ?string $golog_work_schedule_id
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
        'cpf',
        'golog_user_id',
        'golog_driver_id',
        'golog_work_schedule_id',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
        ];
    }

    public function getStoppedStep(): GoLogDriverStep
    {
        if ($this->golog_user_id === null) {
            return GoLogDriverStep::USER;
        }

        if ($this->golog_driver_id === null) {
            return GoLogDriverStep::DRIVER;
        }

        $vehicle = $this->vehicle;
        if ($vehicle === null || $vehicle->golog_tractor_id === null) {
            return GoLogDriverStep::TRACTOR;
        }

        if ($vehicle->golog_equipament_group_id === null) {
            return GoLogDriverStep::EQUIPAMENT_GROUP;
        }

        if ($this->golog_work_schedule_id === null) {
            return GoLogDriverStep::WORK_SCHEDULE;
        }

        return GoLogDriverStep::COMPLETED;
    }

    public function isProvisioned(): bool
    {
        return $this->getStoppedStep() === GoLogDriverStep::COMPLETED;
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
