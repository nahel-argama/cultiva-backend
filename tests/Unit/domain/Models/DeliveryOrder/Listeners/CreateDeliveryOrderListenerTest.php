<?php

namespace Tests\Unit\domain\Models\DeliveryOrder\Listeners;

use Cultiva\Models\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Listeners\CreateDeliveryOrderListener;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\PurchaseFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateDeliveryOrderListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_delegate_to_create_delivery_order_action_when_purchase_created(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $purchase = PurchaseFactory::new()->create([
            'offer_id' => $offer->id,
            'producer_id' => $producer->id,
            'retailer_id' => $retailer->id,
        ]);

        $event = new PurchaseCreated($purchase);

        // Expects
        $actionMock = Mockery::mock(CreateDeliveryOrderAction::class, function (MockInterface $mock) use ($purchase): void {
            $mock->shouldReceive('execute')
                ->once()
                ->with($purchase)
                ->andReturn(Mockery::mock(DeliveryOrder::class));
        });

        $sut = new CreateDeliveryOrderListener($actionMock);

        // Action
        $sut->handle($event);

        // Assert
        $this->assertTrue(true);
    }
}
