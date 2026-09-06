<?php

namespace Database\Factories;

use Cultiva\Models\Vehicle\Enums\CargoType;
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
            'delivery_id' => DeliveryFactory::new(),
            'plate'       => strtoupper($this->faker->unique()->bothify('???#?##')),
            'cargo_type'  => $this->faker->randomElement(CargoType::cases()),
        ];
    }
}
