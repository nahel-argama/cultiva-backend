<?php

namespace Tests\Unit\domain\Models\DeliveryOrder\Listeners;

use Cultiva\Base\Observer\Contracts\EventObserverInterface;
use Cultiva\Models\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Listeners\CreateDeliveryOrderListener;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\PurchaseFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

class CreateDeliveryOrderListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_implement_event_observer_interface(): void
    {
        // Arrange
        $actionMock = Mockery::mock(CreateDeliveryOrderAction::class);

        // Action
        $sut = new CreateDeliveryOrderListener($actionMock);

        // Assert
        $this->assertInstanceOf(EventObserverInterface::class, $sut);
    }

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

    public function test_should_delegate_via_update_when_purchase_created_event_is_received(): void
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
        $sut->update($event);

        // Assert
        $this->addToAssertionCount(1);
    }

    public function test_should_throw_invalid_argument_exception_when_update_receives_unexpected_event(): void
    {
        // Arrange
        $actionMock = Mockery::mock(CreateDeliveryOrderAction::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('execute');
        });
        $sut = new CreateDeliveryOrderListener($actionMock);

        // Action & Assert
        $this->expectException(InvalidArgumentException::class);
        $sut->update(new stdClass);
    }
}
