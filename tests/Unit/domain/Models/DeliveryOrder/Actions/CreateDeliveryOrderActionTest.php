<?php

namespace Tests\Unit\domain\Models\DeliveryOrder\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Address\Address;
use Cultiva\Models\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Database\Factories\AddressFactory;
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
        $producerAddress = AddressFactory::new()->create([
            'company_id' => $producer->company->id,
            'zip' => '89010000',
            'street' => 'Rua do Produtor',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Blumenau',
            'state' => 'SC',
        ]);

        $retailer = RetailerFactory::new()->create();
        $retailerAddress = AddressFactory::new()->create([
            'company_id' => $retailer->company->id,
            'zip' => '89020000',
            'street' => 'Rua do Varejista',
            'number' => '200',
            'neighborhood' => 'Garcia',
            'city' => 'Blumenau',
            'state' => 'SC',
        ]);

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
            'zip' => '89010000',
            'street' => 'Rua do Produtor',
            'number' => '100',
            'neighborhood' => 'Centro',
            'city' => 'Blumenau',
            'state' => 'SC',
        ]);

        $this->assertDatabaseHas('delivery_addresses', [
            'id' => $result->dropoff_address_id,
            'zip' => '89020000',
            'street' => 'Rua do Varejista',
            'number' => '200',
            'neighborhood' => 'Garcia',
            'city' => 'Blumenau',
            'state' => 'SC',
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

    public function test_should_throw_exception_when_producer_has_no_address(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $retailer = RetailerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $retailer->company->id,
        ]);

        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
        ]);

        $purchase = PurchaseFactory::new()->create([
            'offer_id' => $offer->id,
            'producer_id' => $producer->id,
            'retailer_id' => $retailer->id,
        ]);

        $sut = new CreateDeliveryOrderAction();

        // Action & Assert
        $this->expectException(CultivaException::class);
        $sut->execute($purchase);
    }

    public function test_should_throw_exception_when_retailer_has_no_address(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $producer->company->id,
        ]);

        $retailer = RetailerFactory::new()->create();

        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
        ]);

        $purchase = PurchaseFactory::new()->create([
            'offer_id' => $offer->id,
            'producer_id' => $producer->id,
            'retailer_id' => $retailer->id,
        ]);

        $sut = new CreateDeliveryOrderAction();

        // Action & Assert
        $this->expectException(CultivaException::class);
        $sut->execute($purchase);
    }
}
