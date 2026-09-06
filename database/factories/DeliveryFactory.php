<?php

namespace Database\Factories;

use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Delivery\Enums\CnhCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id'   => CompanyFactory::new(),
            'cnh_number'   => $this->faker->unique()->numerify('###########'),
            'cnh_category' => $this->faker->randomElement(CnhCategory::cases()),
        ];
    }
}
