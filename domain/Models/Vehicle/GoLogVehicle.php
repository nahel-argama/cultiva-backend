<?php

namespace Cultiva\Models\Vehicle;

use Carbon\CarbonImmutable;
use Cultiva\Models\Delivery\GoLogDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $golog_driver_id
 * @property-read string $golog_tractor_id
 * @property-read string $golog_equipament_group_id
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?GoLogDriver $gologDriver
 */
class GoLogVehicle extends Model
{
    protected $table = 'golog_vehicles';

    protected $fillable = [
        'golog_driver_id',
        'golog_tractor_id',
        'golog_equipament_group_id',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'golog_driver_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GoLogDriver, $this>
     */
    public function gologDriver(): BelongsTo
    {
        return $this->belongsTo(GoLogDriver::class, 'golog_driver_id');
    }
}
