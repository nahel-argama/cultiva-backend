<?php

namespace Tests\Unit\domain\Models\DeliveryTrip;

use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopType;
use Database\Factories\DeliveryAddressFactory;
use Database\Factories\DeliveryTripFactory;
use Database\Factories\DeliveryTripStopFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryTripStopTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_delivery_trip_stop_with_relations_and_casted_enums(): void
    {
        // Arrange
        $trip = DeliveryTripFactory::new()->create();
        $address = DeliveryAddressFactory::new()->create();

        // Action
        $stop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $address->id,
            'sequence' => 1,
            'stop_type' => DeliveryTripStopType::PICKUP,
            'status' => DeliveryTripStopStatus::ARRIVED,
            'notes' => 'Aguardando no portão',
        ]);

        // Assert
        $this->assertInstanceOf(DeliveryTripStop::class, $stop);
        $this->assertSame(DeliveryTripStopType::PICKUP, $stop->stop_type);
        $this->assertSame(DeliveryTripStopStatus::ARRIVED, $stop->status);
        $this->assertSame(1, $stop->sequence);
        $this->assertSame($trip->id, $stop->trip->id);
        $this->assertSame($address->id, $stop->deliveryAddress->id);
        $this->assertDatabaseHas('delivery_trip_stops', [
            'id' => $stop->id,
            'trip_id' => $trip->id,
            'sequence' => 1,
            'stop_type' => 'pickup',
            'status' => 'arrived',
        ]);
    }
}
