<?php

namespace Tests\Feature\domain\Models\DeliveryTrip\Http\Controllers\DeliveryTripController;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\CargoType\Enums\CargoType as CargoTypeEnum;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
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

class AssignTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_assign_available_trip_to_courier_successfully(): void
    {
        // Arrange
        $cargoType = CargoType::query()->where('code', CargoTypeEnum::REFRIGERATED->value)->first();
        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        $trip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        $address = DeliveryAddressFactory::new()->create();
        $stop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $address->id,
        ]);

        $order = DeliveryOrderFactory::new()->create([
            'trip_id' => $trip->id,
            'pickup_stop_id' => $stop->id,
            'pickup_address_id' => $address->id,
            'dropoff_address_id' => $address->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/assign");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.status', 'assigned');

        $this->assertDatabaseHas('delivery_trips', [
            'id' => $trip->id,
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'status' => DeliveryTripStatus::ASSIGNED->value,
        ]);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $order->id,
            'status' => DeliveryOrderStatus::ASSIGNED->value,
        ]);
    }

    public function test_should_return_409_when_courier_already_has_an_active_trip(): void
    {
        // Arrange
        $cargoType = CargoType::query()->where('code', CargoTypeEnum::DRY->value)->first();
        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::ASSIGNED,
        ]);

        $newTrip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$newTrip->id}/assign");

        // Assert
        $response->assertStatus(409);
    }

    public function test_should_return_409_when_trip_is_already_assigned(): void
    {
        // Arrange
        $cargoType = CargoType::query()->where('code', CargoTypeEnum::DRY->value)->first();
        $delivery = DeliveryFactory::new()->create();
        VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        $trip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/assign");

        // Assert
        $response->assertStatus(409);
    }

    public function test_should_return_422_when_courier_vehicle_cargo_type_is_incompatible(): void
    {
        // Arrange
        $refrigeratedType = CargoType::query()->where('code', CargoTypeEnum::REFRIGERATED->value)->first();
        $dryType = CargoType::query()->where('code', CargoTypeEnum::DRY->value)->first();

        $delivery = DeliveryFactory::new()->create();
        VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $dryType->id,
        ]);

        $trip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $refrigeratedType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/assign");

        // Assert
        $response->assertStatus(422);
    }

    public function test_should_return_422_when_courier_has_no_vehicle(): void
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
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/assign");

        // Assert
        $response->assertStatus(422);
    }

    public function test_should_return_404_when_trip_does_not_exist(): void
    {
        // Arrange
        $delivery = DeliveryFactory::new()->create();
        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/delivery/trips/999999/assign');

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
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/assign");

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $trip = DeliveryTripFactory::new()->create();

        // Action
        $response = $this->postJson("/v1/delivery/trips/{$trip->id}/assign");

        // Assert
        $response->assertUnauthorized();
    }
}
