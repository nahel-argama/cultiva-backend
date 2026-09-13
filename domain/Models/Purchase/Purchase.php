<?php

namespace Cultiva\Models\Purchase;

use Carbon\CarbonImmutable;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read int $id
 * @property-read int $offer_id
 * @property-read int $retailer_id
 * @property-read int $producer_id
 * @property-read string $source_product_id
 * @property-read string $product_name
 * @property-read int $quantity
 * @property-read string $unit_price
 * @property-read string $total_price
 * @property-read CarbonImmutable $created_at
 * @property-read CarbonImmutable $updated_at
 */
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'retailer_id',
        'producer_id',
        'source_product_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Offer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /** @return BelongsTo<Retailer, $this> */
    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }

    /** @return BelongsTo<Producer, $this> */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }
}
