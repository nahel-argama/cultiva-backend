<?php

namespace Tests\Unit\domain\Models\DeliveryOrder\Actions;

use Cultiva\Models\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\PurchaseFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDeliveryOrderActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_delivery_order_and_snapshot_addresses_successfully(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $producerAddress = $producer->company->address;

        $retailer = RetailerFactory::new()->create();
        $retailerAddress = $retailer->company->address;

        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
        ]);

        $purchase = PurchaseFactory::new()->create([
            'offer_id' => $offer->id,
            'producer_id' => $producer->id,
            'retailer_id' => $retailer->id,
            'quantity' => 15,
        ]);

        $sut = new CreateDeliveryOrderAction();

        // Action
        $result = $sut->execute($purchase);

        // Assert
        $this->assertInstanceOf(DeliveryOrder::class, $result);
        $this->assertSame($purchase->id, $result->purchase_id);
        $this->assertSame(15, $result->quantity);
        $this->assertSame(DeliveryOrderStatus::PENDING, $result->status);
        $this->assertNull($result->trip_id);
        $this->assertNull($result->pickup_stop_id);
        $this->assertNull($result->dropoff_stop_id);

        $this->assertDatabaseHas('delivery_addresses', [
            'id' => $result->pickup_address_id,
            'zip' => $producerAddress->zip,
            'street' => $producerAddress->street,
            'number' => $producerAddress->number,
            'neighborhood' => $producerAddress->neighborhood,
            'city' => $producerAddress->city,
            'state' => $producerAddress->state,
        ]);

        $this->assertDatabaseHas('delivery_addresses', [
            'id' => $result->dropoff_address_id,
            'zip' => $retailerAddress->zip,
            'street' => $retailerAddress->street,
            'number' => $retailerAddress->number,
            'neighborhood' => $retailerAddress->neighborhood,
            'city' => $retailerAddress->city,
            'state' => $retailerAddress->state,
        ]);

        $this->assertDatabaseHas('delivery_orders', [
            'id' => $result->id,
            'purchase_id' => $purchase->id,
            'pickup_address_id' => $result->pickup_address_id,
            'dropoff_address_id' => $result->dropoff_address_id,
            'quantity' => 15,
            'status' => DeliveryOrderStatus::PENDING->value,
        ]);
    }
}
