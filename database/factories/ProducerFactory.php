<?php

namespace Database\Factories;

use Cultiva\Models\Producer\Producer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producer>
 */
class ProducerFactory extends Factory
{
    protected $model = Producer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'         => UserFactory::new(),
            'is_company'      => false,
            'document_number' => $this->faker->unique()->numerify('###########'),
            'trade_name'      => 'Sítio ' . $this->faker->lastName(),
            'legal_name'      => $this->faker->name(),
            'phone'           => $this->faker->numerify('119########'),
        ];
    }

    public function company(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_company'      => true,
            'document_number' => $this->faker->unique()->numerify('##############'),
            'legal_name'      => $this->faker->company() . ' LTDA',
        ]);
    }
}
