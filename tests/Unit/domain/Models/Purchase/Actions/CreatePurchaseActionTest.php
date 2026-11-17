<?php

namespace Tests\Unit\domain\Models\Purchase\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Base\Observer\Contracts\EventSubjectInterface;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Purchase\Actions\CreatePurchaseAction;
use Cultiva\Models\Purchase\DTO\CreatePurchaseDTO;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Cultiva\Models\Purchase\Purchase;
use Database\Factories\OfferFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreatePurchaseActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_notify_observers_via_event_subject_when_purchase_is_created(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'unit_price' => '10.00',
            'total_quantity' => 20,
            'reserved_quantity' => 0,
            'status' => OfferStatus::ACTIVE,
        ]);
        $dto = new CreatePurchaseDTO(quantity: 2, expectedUnitPrice: 10.00);

        // Expects
        $eventSubjectMock = Mockery::mock(EventSubjectInterface::class, function (MockInterface $mock) use ($offer, $retailer): void {
            $mock->shouldReceive('notify')
                ->once()
                ->with(Mockery::on(
                    fn (object $event): bool => $event instanceof PurchaseCreated
                        && $event->purchase->offer_id === $offer->id
                        && $event->purchase->retailer_id === $retailer->id
                        && $event->purchase->quantity === 2
                ));
        });

        $sut = new CreatePurchaseAction($eventSubjectMock);

        // Action
        $result = $sut->execute($retailer, $offer->id, $dto);

        // Assert
        $this->assertInstanceOf(Purchase::class, $result);
        $this->assertSame(2, $result->quantity);
        $this->assertDatabaseHas('purchases', [
            'id' => $result->id,
            'offer_id' => $offer->id,
            'retailer_id' => $retailer->id,
        ]);
    }

    public function test_should_not_notify_observers_when_offer_is_inactive(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => OfferStatus::INACTIVE,
        ]);
        $dto = new CreatePurchaseDTO(quantity: 1);

        // Expects
        $eventSubjectMock = Mockery::mock(EventSubjectInterface::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('notify');
        });

        $sut = new CreatePurchaseAction($eventSubjectMock);

        // Action & Assert
        $this->expectException(CultivaException::class);
        $sut->execute($retailer, $offer->id, $dto);
    }
}
