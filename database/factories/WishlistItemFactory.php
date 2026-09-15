<?php

namespace Database\Factories;

use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WishlistItem> */
class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    public function definition(): array
    {
        return [
            'retailer_id' => RetailerFactory::new(),
            'source_product_id' => (string) $this->faker->unique()->numberBetween(1, 999999),
            'product_name' => $this->faker->words(2, true),
            'state' => $this->faker->stateAbbr(),
        ];
    }
}
