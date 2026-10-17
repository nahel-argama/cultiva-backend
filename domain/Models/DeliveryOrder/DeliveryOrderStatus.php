<?php

namespace Cultiva\Models\DeliveryOrder;

use Carbon\CarbonImmutable;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus as DeliveryOrderStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read DeliveryOrderStatusEnum $code
 * @property-read string $description
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 */
class DeliveryOrderStatus extends Model
{
    protected $table = 'delivery_order_statuses';

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
            'code' => DeliveryOrderStatusEnum::class,
        ];
    }
}
