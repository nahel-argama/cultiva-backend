<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferImageController;

use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_delete_image_for_owner_producer(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        $filePath = "product_images/{$offer->id}/1_img01.jpg";
        Storage::disk('public')->put($filePath, 'image data');

        // Action
        $response = $this->deleteJson("/v1/offers/{$offer->id}/images/img01");

        // Assert
        $response->assertNoContent();
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_should_return_404_when_image_does_not_exist(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->deleteJson("/v1/offers/{$offer->id}/images/nonexistent");

        // Assert
        $response->assertNotFound();
    }

    public function test_should_deny_deletion_when_producer_does_not_own_offer(): void
    {
        // Arrange
        Storage::fake('public');
        $ownerProducer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $ownerProducer->id]);
        Sanctum::actingAs($otherProducer->company->user, ['access']);

        $filePath = "product_images/{$offer->id}/1_img01.jpg";
        Storage::disk('public')->put($filePath, 'image data');

        // Action
        $response = $this->deleteJson("/v1/offers/{$offer->id}/images/img01");

        // Assert
        $response->assertForbidden();
        Storage::disk('public')->assertExists($filePath);
    }

    public function test_should_deny_deletion_for_retailer_profile(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->deleteJson("/v1/offers/{$offer->id}/images/img01");

        // Assert
        $response->assertForbidden();
    }
}
