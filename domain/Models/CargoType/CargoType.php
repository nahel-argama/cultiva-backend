<?php

namespace Cultiva\Models\CargoType;

use Carbon\CarbonImmutable;
use Cultiva\Models\CargoType\Enums\CargoType as CargoTypeEnum;
use Database\Factories\CargoTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read int $id
 * @property-read CargoTypeEnum $code
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 */
class CargoType extends Model
{
    /** @use HasFactory<CargoTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'code' => CargoTypeEnum::class,
        ];
    }
}
