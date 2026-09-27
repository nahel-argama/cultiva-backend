<?php

namespace Database\Factories;

use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryTripStop>
 */
class DeliveryTripStopFactory extends Factory
{
    protected $model = DeliveryTripStop::class;

    public function definition(): array
    {
        return [
            'trip_id' => DeliveryTripFactory::new(),
            'delivery_address_id' => DeliveryAddressFactory::new(),
            'sequence' => $this->faker->numberBetween(1, 100),
            'stop_type' => DeliveryTripStopType::PICKUP,
            'status' => DeliveryTripStopStatus::PENDING,
            'arrived_at' => null,
            'completed_at' => null,
            'notes' => null,
        ];
    }
}
