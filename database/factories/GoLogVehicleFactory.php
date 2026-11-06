<?php

namespace Database\Factories;

use Cultiva\Models\Vehicle\GoLogVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoLogVehicle>
 */
class GoLogVehicleFactory extends Factory
{
    protected $model = GoLogVehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'golog_driver_id' => GoLogDriverFactory::new(),
            'golog_tractor_id' => $this->faker->unique()->uuid(),
            'golog_equipament_group_id' => null,
        ];
    }
}
