<?php

namespace Tests\Feature\domain\Models\DeliveryTrip\Http\Controllers\DeliveryTripController;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\CargoType\Enums\CargoType as CargoTypeEnum;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Database\Factories\DeliveryFactory;
use Database\Factories\DeliveryTripFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\VehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_available_trips_matching_courier_vehicle_cargo_type(): void
    {
        // Arrange
        $refrigeratedType = CargoType::query()->where('code', CargoTypeEnum::REFRIGERATED->value)->first();
        $dryType = CargoType::query()->where('code', CargoTypeEnum::DRY->value)->first();

        $delivery = DeliveryFactory::new()->create();
        VehicleFactory::new()->create([
            'delivery_id' => $delivery->id,
            'cargo_type_id' => $refrigeratedType->id,
        ]);

        $matchingTrip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $refrigeratedType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        $differentCargoTrip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $dryType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        $assignedTrip = DeliveryTripFactory::new()->create([
            'cargo_type_id' => $refrigeratedType->id,
            'status' => DeliveryTripStatus::ASSIGNED,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips');

        // Assert
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingTrip->id)
            ->assertJsonPath('data.0.status', 'available')
            ->assertJsonPath('data.0.cargo_type.code', 'refrigerated');
    }

    public function test_should_return_empty_when_courier_has_no_vehicle(): void
    {
        // Arrange
        $delivery = DeliveryFactory::new()->create();
        $cargoType = CargoType::query()->first();
        DeliveryTripFactory::new()->create([
            'cargo_type_id' => $cargoType->id,
            'status' => DeliveryTripStatus::AVAILABLE,
        ]);

        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips');

        // Assert
        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_should_return_403_for_producer_profile(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/delivery/trips');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_401_without_token(): void
    {
        // Action
        $response = $this->getJson('/v1/delivery/trips');

        // Assert
        $response->assertUnauthorized();
    }
}
