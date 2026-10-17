<?php

namespace Cultiva\Models\DeliveryTrip;

use Carbon\CarbonImmutable;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus as DeliveryTripStopStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read DeliveryTripStopStatusEnum $code
 * @property-read string $description
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 */
class DeliveryTripStopStatus extends Model
{
    protected $table = 'delivery_trip_stop_statuses';

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
            'code' => DeliveryTripStopStatusEnum::class,
        ];
    }
}
