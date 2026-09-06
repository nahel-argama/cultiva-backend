<?php

namespace Database\Factories;

use Cultiva\Models\Company\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

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
            'trade_name'      => $this->faker->company(),
            'legal_name'      => $this->faker->company() . ' LTDA',
            'phone'           => $this->faker->numerify('55119########'),
        ];
    }
}
