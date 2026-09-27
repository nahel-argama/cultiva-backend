<?php

namespace Database\Factories;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\CargoType\Enums\CargoType as CargoTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CargoType>
 */
class CargoTypeFactory extends Factory
{
    protected $model = CargoType::class;

    public function definition(): array
    {
        $case = $this->faker->randomElement(CargoTypeEnum::cases());

        return [
            'code' => $case,
        ];
    }
}
