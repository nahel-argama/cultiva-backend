<?php

namespace Database\Factories;

use Cultiva\Models\Producer\Enums\ActivitySegment;
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
            'company_id'       => CompanyFactory::new(),
            'activity_segment' => $this->faker->randomElement(ActivitySegment::cases()),
        ];
    }
}
