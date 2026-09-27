<?php

namespace Database\Factories;

use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryOrder>
 */
class DeliveryOrderFactory extends Factory
{
    protected $model = DeliveryOrder::class;

    public function definition(): array
    {
        return [
            'purchase_id' => PurchaseFactory::new(),
            'trip_id' => null,
            'pickup_stop_id' => null,
            'dropoff_stop_id' => null,
            'pickup_address_id' => DeliveryAddressFactory::new(),
            'dropoff_address_id' => DeliveryAddressFactory::new(),
            'quantity' => $this->faker->numberBetween(1, 100),
            'status' => DeliveryOrderStatus::PENDING,
            'picked_up_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'cancelled_at' => null,
        ];
    }
}
