<?php

namespace Cultiva\Models\DeliveryTrip;

use Carbon\CarbonImmutable;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus as DeliveryTripStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read DeliveryTripStatusEnum $code
 * @property-read string $description
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 */
class DeliveryTripStatus extends Model
{
    protected $table = 'delivery_trip_statuses';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'code',
        'description',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'code' => DeliveryTripStatusEnum::class,
        ];
    }
}
