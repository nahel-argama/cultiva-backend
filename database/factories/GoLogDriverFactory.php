<?php

namespace Database\Factories;

use Cultiva\Models\Delivery\GoLogDriver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoLogDriver>
 */
class GoLogDriverFactory extends Factory
{
    protected $model = GoLogDriver::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new()->for(UserFactory::new()->gologDriver()),
            'cpf' => $this->faker->unique()->numerify('###########'),
            'golog_user_id' => $this->faker->unique()->uuid(),
            'golog_driver_id' => null,
            'golog_work_schedule_id' => null,
        ];
    }
}
