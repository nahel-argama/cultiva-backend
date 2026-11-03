<?php

namespace Cultiva\Models\Category;

use Carbon\CarbonImmutable;
use Cultiva\Models\Category\Enums\Category as CategoryEnum;
use Cultiva\Models\Offer\Offer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read int $id
 * @property-read CategoryEnum $name
 * @property-read CarbonImmutable $created_at
 * @property-read ?CarbonImmutable $updated_at
 */
class Category extends Model
{
    protected $fillable = [
        'name',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'name' => CategoryEnum::class,
        ];
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
