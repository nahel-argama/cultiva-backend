<?php

namespace Database\Factories;

use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAddress>
 */
class DeliveryAddressFactory extends Factory
{
    protected $model = DeliveryAddress::class;

    public function definition(): array
    {
        return [
            'zip' => $this->faker->numerify('########'),
            'street' => $this->faker->streetName(),
            'number' => (string) $this->faker->buildingNumber(),
            'complement' => $this->faker->optional()->secondaryAddress(),
            'reference_point' => $this->faker->optional()->sentence(3),
            'neighborhood' => $this->faker->citySuffix(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'coordinate' => null,
        ];
    }
}
