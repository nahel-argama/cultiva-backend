<?php

namespace Cultiva\Models\Offer;

use Carbon\CarbonImmutable;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Purchase\Purchase;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read int $id
 * @property-read int $producer_id
 * @property-read int $category_id
 * @property-read string $source_product_id
 * @property-read string $product_name
 * @property-read string $unit_price
 * @property-read int $total_quantity
 * @property-read int $reserved_quantity
 * @property-read OfferStatus $status
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 * @property-read Producer $producer
 * @property-read Category $category
 * @property-read Collection<int, Purchase> $purchases
 */
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    protected $fillable = [
        'producer_id',
        'category_id',
        'source_product_id',
        'product_name',
        'unit_price',
        'total_quantity',
        'reserved_quantity',
        'status',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'status' => OfferStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Producer, $this>
     */
    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function availableQuantity(): int
    {
        return $this->total_quantity - $this->reserved_quantity;
    }

    public function isVisible(): bool
    {
        return $this->status === OfferStatus::ACTIVE && $this->availableQuantity() > 0;
    }
}
