<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Models\Offer\Actions\ListAvailableOffersAction;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAvailableOffersActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_order_available_offers_by_distance_relative_to_user(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $retailer->company->address->update([
            'coordinate' => 'POINT(-49.0629788 -26.9244749)',
        ]);
        $user = $retailer->company->user->fresh();

        $closerProducer = ProducerFactory::new()->create();
        $closerProducer->company->address->update([
            'coordinate' => 'POINT(-49.0700000 -26.9300000)',
        ]);
        $closerOffer = OfferFactory::new()->create([
            'producer_id' => $closerProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now()->subHours(2),
        ]);

        $furtherProducer = ProducerFactory::new()->create();
        $furtherProducer->company->address->update([
            'coordinate' => 'POINT(-49.1500000 -26.9900000)',
        ]);
        $furtherOffer = OfferFactory::new()->create([
            'producer_id' => $furtherProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now()->subHour(),
        ]);

        $sut = new ListAvailableOffersAction();

        // Action
        $result = $sut->execute($user, 1, 15);

        // Assert
        $items = $result->items();
        $this->assertCount(2, $items);
        $this->assertSame($closerOffer->id, $items[0]->id);
        $this->assertEquals(0.93, $items[0]->distance_km);

        $this->assertSame($furtherOffer->id, $items[1]->id);
        $this->assertEquals(11.29, $items[1]->distance_km);
    }
}
