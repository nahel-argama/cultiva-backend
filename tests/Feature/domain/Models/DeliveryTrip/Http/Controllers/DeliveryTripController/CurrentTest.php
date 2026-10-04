<?php

namespace Tests\Feature\domain\Models\DeliveryTrip\Http\Controllers\DeliveryTripController;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\CargoType\Enums\CargoType as CargoTypeEnum;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Database\Factories\DeliveryAddressFactory;
use Database\Factories\DeliveryFactory;
use Database\Factories\DeliveryOrderFactory;
use Database\Factories\DeliveryTripFactory;
use Database\Factories\DeliveryTripStopFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\VehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrentTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_active_trip_when_courier_has_assigned_trip(): void
    {
        // Arrange
        $cargoType = CargoType::query()->where('code', CargoTypeEnum::DRY->value)->first();
        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        $trip = DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::ASSIGNED,
        ]);

        $address = DeliveryAddressFactory::new()->create();
        $stop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $address->id,
            'sequence' => 1,
        ]);

        $order = DeliveryOrderFactory::new()->create([
            'trip_id' => $trip->id,
            'pickup_stop_id' => $stop->id,
            'pickup_address_id' => $address->id,
            'dropoff_address_id' => $address->id,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/current');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.cargo_type.code', 'dry')
            ->assertJsonCount(1, 'data.stops')
            ->assertJsonPath('data.stops.0.id', $stop->id)
            ->assertJsonCount(1, 'data.orders')
            ->assertJsonPath('data.orders.0.id', $order->id);
    }

    public function test_should_return_active_trip_when_courier_has_in_progress_trip(): void
    {
        // Arrange
        $cargoType = CargoType::query()->where('code', CargoTypeEnum::DRY->value)->first();
        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        $trip = DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::IN_PROGRESS,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/current');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_should_return_null_data_when_courier_has_no_active_trip(): void
    {
        // Arrange
        $cargoType = CargoType::query()->first();
        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::COMPLETED,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/current');

        // Assert
        $response->assertOk()
            ->assertExactJson(['data' => null]);
    }

    public function test_should_return_403_for_producer_profile(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips/current');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_401_without_token(): void
    {
        // Action
        $response = $this->getJson('/v1/delivery/trips/current');

        // Assert
        $response->assertUnauthorized();
    }
}
