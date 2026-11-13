<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferImageController;

use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_upload_images_to_existing_offer_for_owner_producer(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        $payload = [
            'images' => [
                UploadedFile::fake()->image('extra1.jpg'),
                UploadedFile::fake()->image('extra2.png'),
            ],
        ];

        // Action
        $response = $this->post("/v1/offers/{$offer->id}/images", $payload);

        // Assert
        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.sequence', 1)
            ->assertJsonPath('data.1.sequence', 2);

        $files = Storage::disk('public')->files("product_images/{$offer->id}");
        $this->assertCount(2, $files);
    }

    public function test_should_reject_upload_when_total_images_exceed_limit_of_three(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Already 2 images on disk
        Storage::disk('public')->put("product_images/{$offer->id}/1_img1.jpg", 'content');
        Storage::disk('public')->put("product_images/{$offer->id}/2_img2.jpg", 'content');

        // Trying to upload 2 more (2 + 2 = 4 > 3)
        $payload = [
            'images' => [
                UploadedFile::fake()->image('img3.jpg'),
                UploadedFile::fake()->image('img4.jpg'),
            ],
        ];

        // Action
        $response = $this->postJson("/v1/offers/{$offer->id}/images", $payload);

        // Assert
        $response->assertUnprocessable();
    }

    public function test_should_deny_upload_when_producer_does_not_own_offer(): void
    {
        // Arrange
        Storage::fake('public');
        $ownerProducer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $ownerProducer->id]);
        Sanctum::actingAs($otherProducer->company->user, ['access']);

        $payload = [
            'images' => [
                UploadedFile::fake()->image('hack.jpg'),
            ],
        ];

        // Action
        $response = $this->postJson("/v1/offers/{$offer->id}/images", $payload);

        // Assert
        $response->assertForbidden();
    }

    public function test_should_deny_upload_for_retailer_profile(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        $payload = [
            'images' => [
                UploadedFile::fake()->image('photo.jpg'),
            ],
        ];

        // Action
        $response = $this->postJson("/v1/offers/{$offer->id}/images", $payload);

        // Assert
        $response->assertForbidden();
    }
}
