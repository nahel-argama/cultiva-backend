<?php

namespace Database\Factories;

use Cultiva\Models\HarvestForecast\HarvestForecast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HarvestForecast>
 */
class HarvestForecastFactory extends Factory
{
    protected $model = HarvestForecast::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->addDays(10)->toDateString();

        return [
            'producer_id' => ProducerFactory::new(),
            'source_product_id' => '0002',
            'product_name' => 'Tomate Italiano',
            'quantity' => 300,
            'start_date' => $startDate,
            'end_date' => $startDate,
        ];
    }
}
