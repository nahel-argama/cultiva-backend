<?php

namespace Database\Factories;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delivery_id'   => DeliveryFactory::new(),
            'cargo_type_id' => $this->faker->randomElement(CargoType::pluck('id')->toArray()),
            'plate'         => strtoupper($this->faker->unique()->bothify('???#?##')),
        ];
    }
}
