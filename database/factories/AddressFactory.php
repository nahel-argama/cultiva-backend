<?php

namespace Database\Factories;

use Cultiva\Models\Address\Address;
use Cultiva\Models\Producer\Producer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{

    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'addressable_type' => Producer::class,
            'addressable_id'   => ProducerFactory::new(),
            'zip'              => $this->faker->numerify('########'),
            'street'           => $this->faker->streetName(),
            'number'           => $this->faker->buildingNumber(),
            'complement'       => $this->faker->optional()->sentence(3),
            'reference_point'  => $this->faker->optional()->sentence(3),
            'neighborhood'     => $this->faker->citySuffix(),
            'city'             => $this->faker->city(),
            'state'            => $this->faker->stateAbbr(),
            'coordinate'       => "POINT({$this->faker->longitude()} {$this->faker->latitude()})",
        ];
    }
}
