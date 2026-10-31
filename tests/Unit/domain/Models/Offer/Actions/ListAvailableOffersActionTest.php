<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Models\Offer\Actions\ListAvailableOffersAction;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\AddressFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListAvailableOffersActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_order_available_offers_by_distance_relative_to_user_with_nulls_last(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $retailer->company->id,
            'coordinate' => 'POINT(-49.0629788 -26.9244749)',
        ]);
        $user = $retailer->company->user->fresh();

        $closerProducer = ProducerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $closerProducer->company->id,
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
        AddressFactory::new()->create([
            'company_id' => $furtherProducer->company->id,
            'coordinate' => 'POINT(-49.1500000 -26.9900000)',
        ]);
        $furtherOffer = OfferFactory::new()->create([
            'producer_id' => $furtherProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now()->subHour(),
        ]);

        $noCoordinateProducer = ProducerFactory::new()->create();
        $noCoordinateOffer = OfferFactory::new()->create([
            'producer_id' => $noCoordinateProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now(),
        ]);

        $sut = new ListAvailableOffersAction();

        // Action
        $result = $sut->execute($user, 1, 15);

        // Assert
        $items = $result->items();
        $this->assertCount(3, $items);
        $this->assertSame($closerOffer->id, $items[0]->id);
        $this->assertEquals(0.93, $items[0]->distance_km);

        $this->assertSame($furtherOffer->id, $items[1]->id);
        $this->assertEquals(11.29, $items[1]->distance_km);

        $this->assertSame($noCoordinateOffer->id, $items[2]->id);
        $this->assertNull($items[2]->distance_km);
    }

    public function test_should_return_null_distance_and_order_by_newest_when_user_has_no_coordinate(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $user = $retailer->company->user;

        $producer = ProducerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $producer->company->id,
            'coordinate' => 'POINT(-49.0700000 -26.9300000)',
        ]);

        $olderOffer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now()->subHour(),
        ]);
        $newerOffer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now(),
        ]);

        $sut = new ListAvailableOffersAction();

        // Action
        $result = $sut->execute($user, 1, 15);

        // Assert
        $items = $result->items();
        $this->assertCount(2, $items);
        $this->assertSame($newerOffer->id, $items[0]->id);
        $this->assertNull($items[0]->distance_km);

        $this->assertSame($olderOffer->id, $items[1]->id);
        $this->assertNull($items[1]->distance_km);
    }
}
