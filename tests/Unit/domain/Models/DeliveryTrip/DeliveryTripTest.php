<?php

namespace Tests\Unit\domain\Models\DeliveryTrip;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Cultiva\Models\Vehicle\Vehicle;
use Database\Factories\CargoTypeFactory;
use Database\Factories\DeliveryFactory;
use Database\Factories\DeliveryTripFactory;
use Database\Factories\VehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_delivery_trip_with_relations_and_casted_status(): void
    {
        // Arrange
        $cargoType = CargoType::query()->first();
        $delivery = DeliveryFactory::new()->create();
        $vehicle = VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $cargoType->id,
        ]);

        // Action
        $trip = DeliveryTripFactory::new()->create([
            'delivery_id' => $delivery->id,
            'vehicle_id' => $vehicle->id,
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::ASSIGNED,
        ]);

        // Assert
        $this->assertInstanceOf(DeliveryTrip::class, $trip);
        $this->assertSame(DeliveryTripStatus::ASSIGNED, $trip->status);
        $this->assertSame($delivery->id, $trip->delivery->id);
        $this->assertSame($vehicle->id, $trip->vehicle->id);
        $this->assertSame($cargoType->id, $trip->cargoType->id);
        $this->assertDatabaseHas('delivery_trips', [
            'id' => $trip->id,
            'cargo_type_id' => $cargoType->id,
            'status' => 'assigned',
        ]);
    }
}
