<?php

namespace Database\Factories;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'producer_id' => ProducerFactory::new(),
            'category_id' => CategoryFactory::new(),
            'source_product_id' => (string) $this->faker->unique()->numberBetween(1, 999999),
            'product_name' => $this->faker->words(2, true),
            'unit_price' => $this->faker->randomFloat(2, 1, 9999),
            'total_quantity' => $this->faker->numberBetween(1, 1000),
            'reserved_quantity' => 0,
            'status' => OfferStatus::INACTIVE,
        ];
    }
}
