<?php

namespace Tests\Feature\domain\Models\DeliveryTrip\Http\Controllers\DeliveryTripController;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\CargoType\Enums\CargoType as CargoTypeEnum;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Database\Factories\DeliveryAddressFactory;
use Database\Factories\DeliveryFactory;
use Database\Factories\DeliveryOrderFactory;
use Database\Factories\DeliveryTripFactory;
use Database\Factories\DeliveryTripStopFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\VehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinishedTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_completed_trips_for_authenticated_courier_ordered_by_completed_at_desc(): void
    {
        // Arrange
        $cargoType = CargoType::query()->where('code', CargoTypeEnum::REFRIGERATED->value)->first();

        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        $olderCompletedTrip = DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::COMPLETED,
            'started_at' => now()->subHours(5),
            'completed_at' => now()->subHours(4),
        ]);

        $newerCompletedTrip = DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::COMPLETED,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);

        $address = DeliveryAddressFactory::new()->create();
        $stop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $newerCompletedTrip->id,
            'delivery_address_id' => $address->id,
            'sequence' => 1,
            'status' => DeliveryTripStopStatus::COMPLETED,
            'completed_at' => now()->subHour(),
        ]);

        $order = DeliveryOrderFactory::new()->create([
            'trip_id' => $newerCompletedTrip->id,
            'pickup_stop_id' => $stop->id,
            'pickup_address_id' => $address->id,
            'dropoff_address_id' => $address->id,
            'status' => DeliveryOrderStatus::DELIVERED,
            'delivered_at' => now()->subHour(),
        ]);

        DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::IN_PROGRESS,
        ]);

        DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::CANCELLED,
        ]);

        $otherDelivery = DeliveryFactory::new()->create();
        $otherVehicle = VehicleFactory::new()->create([
            'delivery_id' => $otherDelivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        DeliveryTripFactory::new()->create([
            'delivery_id' => $otherDelivery->id,
            'vehicle_id' => $otherVehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::COMPLETED,
            'completed_at' => now()->subMinutes(10),
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/finished');

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newerCompletedTrip->id)
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.cargo_type.code', 'refrigerated')
            ->assertJsonCount(1, 'data.0.stops')
            ->assertJsonPath('data.0.stops.0.id', $stop->id)
            ->assertJsonCount(1, 'data.0.orders')
            ->assertJsonPath('data.0.orders.0.id', $order->id)
            ->assertJsonPath('data.1.id', $olderCompletedTrip->id);
    }

    public function test_should_return_403_for_producer_profile(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/finished');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/finished');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_401_without_token(): void
    {
        // Action
        $response = $this->getJson('/v1/delivery/trips/finished');

        // Assert
        $response->assertUnauthorized();
    }
}
