<?php

namespace Cultiva\Models\HarvestForecast;

use Carbon\CarbonImmutable;
use Cultiva\Models\Producer\Producer;
use Database\Factories\HarvestForecastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $producer_id
 * @property-read string $source_product_id
 * @property-read string $product_name
 * @property-read int $quantity
 * @property-read CarbonImmutable $start_date
 * @property-read CarbonImmutable $end_date
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read Producer $producer
 */
class HarvestForecast extends Model
{
    /** @use HasFactory<HarvestForecastFactory> */
    use HasFactory;

    protected $fillable = [
        'producer_id',
        'source_product_id',
        'product_name',
        'quantity',
        'start_date',
        'end_date',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Producer, $this>
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }
}
