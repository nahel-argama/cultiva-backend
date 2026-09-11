<?php

namespace Cultiva\Models\Offer;

use Cultiva\Models\Category\Category;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Producer\Producer;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

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

    public function availableQuantity(): int
    {
        return $this->total_quantity - $this->reserved_quantity;
    }

    public function isVisible(): bool
    {
        return $this->status === OfferStatus::ACTIVE && $this->availableQuantity() > 0;
    }
}
