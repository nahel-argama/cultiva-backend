<?php

namespace Database\Factories;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryTrip>
 */
class DeliveryTripFactory extends Factory
{
    protected $model = DeliveryTrip::class;

    public function definition(): array
    {
        return [
            'delivery_id' => null,
            'vehicle_id' => null,
            'cargo_type_id' => $this->faker->randomElement(CargoType::pluck('id')->toArray()),
            'status' => DeliveryTripStatus::AVAILABLE,
            'started_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
