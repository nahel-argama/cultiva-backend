<?php

namespace Tests\Feature\domain\Models\DeliveryTrip\Http\Controllers\DeliveryTripController;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopType;
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

class ConfirmStopTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_confirm_pickup_stop_and_set_orders_in_transit_and_trip_in_progress(): void
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

        $pickupAddress = DeliveryAddressFactory::new()->create();
        $dropoffAddress = DeliveryAddressFactory::new()->create();

        $pickupStop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $pickupAddress->id,
            'stop_type' => DeliveryTripStopType::PICKUP,
            'status' => DeliveryTripStopStatus::PENDING,
            'sequence' => 1,
        ]);

        $dropoffStop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $dropoffAddress->id,
            'stop_type' => DeliveryTripStopType::DROPOFF,
            'status' => DeliveryTripStopStatus::PENDING,
            'sequence' => 2,
        ]);

        $order = DeliveryOrderFactory::new()->create([
            'trip_id' => $trip->id,
            'pickup_stop_id' => $pickupStop->id,
            'dropoff_stop_id' => $dropoffStop->id,
            'pickup_address_id' => $pickupAddress->id,
            'dropoff_address_id' => $dropoffAddress->id,
            'status' => DeliveryOrderStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/stops/{$pickupStop->id}/confirm");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $pickupStop->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.stop_type', 'pickup');

        $this->assertDatabaseHas('delivery_trip_stops', [
            'id' => $pickupStop->id,
            'status' => DeliveryTripStopStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $order->id,
            'status' => DeliveryOrderStatus::IN_TRANSIT->value,
        ]);

        $this->assertDatabaseHas('delivery_trips', [
            'id' => $trip->id,
            'status' => DeliveryTripStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_should_confirm_dropoff_stop_and_set_order_delivered_and_complete_trip(): void
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
            'started_at' => now()->subHour(),
        ]);

        $pickupAddress = DeliveryAddressFactory::new()->create();
        $dropoffAddress = DeliveryAddressFactory::new()->create();

        $pickupStop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $pickupAddress->id,
            'stop_type' => DeliveryTripStopType::PICKUP,
            'status' => DeliveryTripStopStatus::COMPLETED,
            'sequence' => 1,
            'completed_at' => now()->subMinutes(30),
        ]);

        $dropoffStop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $dropoffAddress->id,
            'stop_type' => DeliveryTripStopType::DROPOFF,
            'status' => DeliveryTripStopStatus::PENDING,
            'sequence' => 2,
        ]);

        $order = DeliveryOrderFactory::new()->create([
            'trip_id' => $trip->id,
            'pickup_stop_id' => $pickupStop->id,
            'dropoff_stop_id' => $dropoffStop->id,
            'pickup_address_id' => $pickupAddress->id,
            'dropoff_address_id' => $dropoffAddress->id,
            'status' => DeliveryOrderStatus::IN_TRANSIT,
            'picked_up_at' => now()->subMinutes(30),
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/stops/{$dropoffStop->id}/confirm");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $dropoffStop->id)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.stop_type', 'dropoff');

        $this->assertDatabaseHas('delivery_trip_stops', [
            'id' => $dropoffStop->id,
            'status' => DeliveryTripStopStatus::COMPLETED->value,
        ]);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $order->id,
            'status' => DeliveryOrderStatus::DELIVERED->value,
        ]);

        $this->assertDatabaseHas('delivery_trips', [
            'id' => $trip->id,
            'status' => DeliveryTripStatus::COMPLETED->value,
        ]);
    }

    public function test_should_return_422_when_stop_is_already_completed(): void
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

        $pickupAddress = DeliveryAddressFactory::new()->create();
        $stop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $pickupAddress->id,
            'status' => DeliveryTripStopStatus::COMPLETED,
            'sequence' => 1,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/stops/{$stop->id}/confirm");

        // Assert
        $response->assertStatus(422);
    }

    public function test_should_return_422_when_previous_stop_in_sequence_is_not_completed(): void
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
        DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $address->id,
            'status' => DeliveryTripStopStatus::PENDING,
            'sequence' => 1,
        ]);

        $stop2 = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $address->id,
            'status' => DeliveryTripStopStatus::PENDING,
            'sequence' => 2,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/stops/{$stop2->id}/confirm");

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

        $address = DeliveryAddressFactory::new()->create();
        $stop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $address->id,
            'status' => DeliveryTripStopStatus::PENDING,
            'sequence' => 1,
        ]);

        Sanctum::actingAs($courierA->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/stops/{$stop->id}/confirm");

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_404_when_stop_does_not_exist(): void
    {
        // Arrange
        $delivery = DeliveryFactory::new()->create();
        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/delivery/stops/999999/confirm');

        // Assert
        $response->assertNotFound();
    }

    public function test_should_return_403_for_producer_profile(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/delivery/stops/1/confirm');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_401_without_token(): void
    {
        // Action
        $response = $this->postJson('/v1/delivery/stops/1/confirm');

        // Assert
        $response->assertUnauthorized();
    }
}
