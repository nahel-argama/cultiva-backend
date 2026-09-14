<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Models\Offer\Actions\GetProducerOfferAction;
use Cultiva\Models\Offer\Offer;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetProducerOfferActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_offer_belonging_to_producer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $sut = new GetProducerOfferAction();

        // Action
        $result = $sut->execute($producer, $offer->id);

        // Assert
        $this->assertInstanceOf(Offer::class, $result);
        $this->assertSame($offer->id, $result->id);
    }

    public function test_should_throw_model_not_found_when_offer_belongs_to_another_producer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $otherProducer->id]);
        $sut = new GetProducerOfferAction();

        // Action & Assert
        $this->expectException(ModelNotFoundException::class);
        $sut->execute($producer, $offer->id);
    }
}
