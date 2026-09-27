<?php

namespace Tests\Unit\domain\Models\DeliveryOrder;

use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Cultiva\Models\Purchase\Purchase;
use Database\Factories\DeliveryAddressFactory;
use Database\Factories\DeliveryOrderFactory;
use Database\Factories\DeliveryTripFactory;
use Database\Factories\DeliveryTripStopFactory;
use Database\Factories\PurchaseFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_delivery_order_with_relations_and_casted_status(): void
    {
        // Arrange
        $purchase = PurchaseFactory::new()->create();
        $trip = DeliveryTripFactory::new()->create();
        $pickupAddress = DeliveryAddressFactory::new()->create();
        $dropoffAddress = DeliveryAddressFactory::new()->create();
        $pickupStop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $pickupAddress->id,
        ]);
        $dropoffStop = DeliveryTripStopFactory::new()->create([
            'trip_id' => $trip->id,
            'delivery_address_id' => $dropoffAddress->id,
        ]);

        // Action
        $order = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase->id,
            'trip_id' => $trip->id,
            'pickup_stop_id' => $pickupStop->id,
            'dropoff_stop_id' => $dropoffStop->id,
            'pickup_address_id' => $pickupAddress->id,
            'dropoff_address_id' => $dropoffAddress->id,
            'quantity' => 10,
            'status' => DeliveryOrderStatus::IN_TRANSIT,
        ]);

        // Assert
        $this->assertInstanceOf(DeliveryOrder::class, $order);
        $this->assertSame(DeliveryOrderStatus::IN_TRANSIT, $order->status);
        $this->assertSame(10, $order->quantity);
        $this->assertSame($purchase->id, $order->purchase->id);
        $this->assertSame($trip->id, $order->trip->id);
        $this->assertSame($pickupStop->id, $order->pickupStop->id);
        $this->assertSame($dropoffStop->id, $order->dropoffStop->id);
        $this->assertSame($pickupAddress->id, $order->pickupAddress->id);
        $this->assertSame($dropoffAddress->id, $order->dropoffAddress->id);
        $this->assertDatabaseHas('delivery_orders', [
            'id' => $order->id,
            'purchase_id' => $purchase->id,
            'quantity' => 10,
            'status' => 'in_transit',
        ]);
    }
}
