<?php

namespace Cultiva\Models\Vehicle;

use Carbon\CarbonImmutable;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Vehicle\Enums\CargoType;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * @property-read int $id
 * @property-read int $delivery_id
 * @property-read string $plate
 * @property-read CargoType $cargo_type
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read ?CarbonImmutable $deleted_at
 * @property-read ?Delivery $delivery
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_id',
        'plate',
        'cargo_type',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'cargo_type' => CargoType::class,
        ];
    }

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
