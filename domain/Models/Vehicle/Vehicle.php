<?php

namespace Cultiva\Models\Vehicle;

use Carbon\CarbonImmutable;
use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\Delivery\Delivery;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $delivery_id
 * @property-read int $cargo_type_id
 * @property-read string $plate
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Delivery $delivery
 * @property-read ?CargoType $cargoType
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_id',
        'cargo_type_id',
        'plate',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'cargo_type_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<CargoType, $this>
     */
    public function cargoType(): BelongsTo
    {
        return $this->belongsTo(CargoType::class);
    }
}
