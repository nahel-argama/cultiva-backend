<?php

namespace Tests\Feature\domain\Models\Offer\Http\Controllers\OfferController;

use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_show_own_offer_without_exposing_producer_id(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers/'.$offer->id);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $offer->id)
            ->assertJsonMissingPath('data.producer_id');
    }

    public function test_should_return_404_for_another_producers_offer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $otherProducer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers/'.$offer->id);

        // Assert
        $response->assertNotFound();
    }

    public function test_should_return_404_for_missing_offer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers/999999');

        // Assert
        $response->assertNotFound();
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $url = '/v1/offers/1';

        // Action
        $response = $this->getJson($url);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_should_return_403_for_refresh_token(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['refresh']);

        // Action
        $response = $this->getJson('/v1/offers/1');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers/1');

        // Assert
        $response->assertForbidden();
    }
}
