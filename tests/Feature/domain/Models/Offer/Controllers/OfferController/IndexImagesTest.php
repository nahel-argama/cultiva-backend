<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_include_images_in_offer_index_for_producer(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offerWithImages = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $offerWithoutImages = OfferFactory::new()->create(['producer_id' => $producer->id]);

        Storage::disk('public')->put("product_images/{$offerWithImages->id}/1_img01.jpg", 'img1');
        Storage::disk('public')->put("product_images/{$offerWithImages->id}/2_img02.png", 'img2');

        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk();

        $data = collect($response->json('data'));
        $itemWithImages = $data->firstWhere('id', $offerWithImages->id);
        $itemWithoutImages = $data->firstWhere('id', $offerWithoutImages->id);

        $this->assertNotNull($itemWithImages);
        $this->assertCount(2, $itemWithImages['images']);
        $this->assertSame(1, $itemWithImages['images'][0]['sequence']);
        $this->assertSame('img01', $itemWithImages['images'][0]['id']);
        $this->assertSame(2, $itemWithImages['images'][1]['sequence']);
        $this->assertSame('img02', $itemWithImages['images'][1]['id']);

        $this->assertNotNull($itemWithoutImages);
        $this->assertSame([], $itemWithoutImages['images']);
    }

    public function test_should_include_images_in_offer_index_for_retailer(): void
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

        Storage::disk('public')->put("product_images/{$offer->id}/1_tomato.jpg", 'content');

        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk();
        $data = collect($response->json('data'));
        $item = $data->firstWhere('id', $offer->id);

        $this->assertNotNull($item);
        $this->assertCount(1, $item['images']);
        $this->assertSame(1, $item['images'][0]['sequence']);
        $this->assertSame('tomato', $item['images'][0]['id']);
    }
}
