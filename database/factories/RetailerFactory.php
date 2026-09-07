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
            'company_id'    => CompanyFactory::new()->for(UserFactory::new()->retailer()),
            'business_type' => $this->faker->randomElement(BusinessType::cases()),
        ];
    }
}
