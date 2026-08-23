<?php

namespace Database\Factories;

use Cultiva\Models\Retailer\Enums\BusinessType;
use Cultiva\Models\Retailer\Retailer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Retailer>
 */
class RetailerFactory extends Factory
{
    protected $model = Retailer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'         => UserFactory::new(),
            'document_number' => $this->faker->unique()->numerify('##############'),
            'trade_name'      => 'Hortifruti ' . $this->faker->lastName(),
            'legal_name'      => $this->faker->company() . ' LTDA',
            'business_type'   => $this->faker->randomElement(BusinessType::cases()),
            'phone'           => $this->faker->numerify('119########'),
        ];
    }
}
