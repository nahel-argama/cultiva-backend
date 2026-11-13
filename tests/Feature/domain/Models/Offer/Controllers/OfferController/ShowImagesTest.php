<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_include_images_in_show_offer_for_producer(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        Storage::disk('public')->put("product_images/{$offer->id}/1_apple.jpg", 'img1');
        Storage::disk('public')->put("product_images/{$offer->id}/2_banana.jpg", 'img2');

        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson("/v1/offers/{$offer->id}");

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.id', 'apple')
            ->assertJsonPath('data.images.0.sequence', 1)
            ->assertJsonPath('data.images.1.id', 'banana')
            ->assertJsonPath('data.images.1.sequence', 2);
    }

    public function test_should_return_empty_images_array_when_offer_has_no_images(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson("/v1/offers/{$offer->id}");

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.images', []);
    }

    public function test_should_include_images_in_show_offer_for_retailer(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'status' => 'active',
            'total_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Storage::disk('public')->put("product_images/{$offer->id}/1_carrot.webp", 'content');

        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson("/v1/offers/{$offer->id}");

        // Assert
        $response->assertOk()
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.images.0.id', 'carrot')
            ->assertJsonPath('data.images.0.sequence', 1);
    }
}
