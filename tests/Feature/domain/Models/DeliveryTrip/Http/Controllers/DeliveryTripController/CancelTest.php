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
use Database\Factories\VehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CancelTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_cancel_assigned_trip_and_reset_to_available_when_no_package_picked_up(): void
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
            'status' => DeliveryTripStopStatus::PENDING,
        ]);

        $order = DeliveryOrderFactory::new()->create([
            'trip_id' => $trip->id,
            'pickup_stop_id' => $stop->id,
            'pickup_address_id' => $address->id,
            'dropoff_address_id' => $address->id,
            'status' => DeliveryOrderStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', 'available');

        $this->assertDatabaseHas('delivery_trips', [
            'id' => $trip->id,
            'status' => DeliveryTripStatus::AVAILABLE->value,
            'delivery_id' => null,
            'vehicle_id' => null,
        ]);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $order->id,
            'status' => DeliveryOrderStatus::PENDING->value,
        ]);
    }

    public function test_should_return_422_when_trip_is_already_in_progress(): void
    {
        // Arrange
        $cargoType = CargoType::query()->first();
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
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertStatus(422);
    }

    public function test_should_return_422_when_any_order_has_been_picked_up(): void
    {
        // Arrange
        $cargoType = CargoType::query()->first();
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
        ]);

        DeliveryOrderFactory::new()->create([
            'trip_id' => $trip->id,
            'pickup_stop_id' => $stop->id,
            'pickup_address_id' => $address->id,
            'dropoff_address_id' => $address->id,
            'status' => DeliveryOrderStatus::IN_TRANSIT,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertStatus(422);
    }

    public function test_should_return_403_when_courier_is_not_assigned_to_the_trip(): void
    {
        // Arrange
        $cargoType = CargoType::query()->first();
        $courierA = DeliveryFactory::new()->create();
        $courierB = DeliveryFactory::new()->create();
        $vehicleB = VehicleFactory::new()->create([
            'delivery_id' => $courierB->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        $trip = DeliveryTripFactory::new()->create([
            'delivery_id' => $courierB->id,
            'vehicle_id' => $vehicleB->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($courierA->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_422_when_trip_is_already_available(): void
    {
        // Arrange
        $cargoType = CargoType::query()->first();
        $delivery = DeliveryFactory::new()->create();
        $trip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertStatus(422);
    }

    public function test_should_return_404_when_trip_does_not_exist(): void
    {
        // Arrange
        $delivery = DeliveryFactory::new()->create();
        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/delivery/trips/999999/cancel');

        // Assert
        $response->assertNotFound();
    }

    public function test_should_return_403_for_producer_profile(): void
    {
        // Arrange
        $trip = DeliveryTripFactory::new()->create();
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $trip = DeliveryTripFactory::new()->create();

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/cancel");

        // Assert
        $response->assertUnauthorized();
    }
}
