<?php

namespace Cultiva\Models\Wishlist;

use Carbon\CarbonImmutable;
use Cultiva\Models\Retailer\Retailer;
use Database\Factories\WishlistItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $retailer_id
 * @property-read string $source_product_id
 * @property-read string $product_name
 * @property-read string $state
 * @property-read CarbonImmutable $created_at
 * @property-read CarbonImmutable $updated_at
 */
class WishlistItem extends Model
{
    /** @use HasFactory<WishlistItemFactory> */
    use HasFactory;

    protected $fillable = [
        'retailer_id',
        'source_product_id',
        'product_name',
        'state',
    ];

    /** @return BelongsTo<Retailer, $this> */
    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }
}
