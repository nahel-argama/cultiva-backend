<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Database\Factories\PurchaseFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompareTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_compare_offers_and_recommend_the_one_with_more_wins(): void
    {
        $retailer = RetailerFactory::new()->create();
        $coordinate = 'POINT(-49.07 -26.93)';
        $retailer->company->address->update(['coordinate' => $coordinate]);
        $first = OfferFactory::new()->create([
            'source_product_id' => 'tomato',
            'unit_price' => '5.00',
            'image_path' => 'offers/tomato.jpg',
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => OfferStatus::ACTIVE,
        ]);
        $second = OfferFactory::new()->create([
            'source_product_id' => 'tomato',
            'unit_price' => '7.00',
            'total_quantity' => 20,
            'reserved_quantity' => 0,
            'status' => OfferStatus::ACTIVE,
        ]);
        $first->producer->company->address->update(['coordinate' => $coordinate]);
        $second->producer->company->address->update(['coordinate' => 'POINT(-49.15 -26.99)']);
        Sanctum::actingAs($retailer->company->user, ['access']);

        $url = "/v1/offers/compare?first_offer_id={$first->id}&second_offer_id={$second->id}";
        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.offers.0.id', $first->id)
            ->assertJsonPath('data.offers.0.image_url', Storage::disk('public')->url('offers/tomato.jpg'))
            ->assertJsonPath('data.criteria.unit_price', $first->id)
            ->assertJsonPath('data.criteria.distance_km', $first->id)
            ->assertJsonPath('data.criteria.available_quantity', $second->id)
            ->assertJsonPath('data.criteria.recent_sales', null)
            ->assertJsonPath('data.recommended_offer_id', $first->id);

        PurchaseFactory::new()->create([
            'offer_id' => $second->id,
            'producer_id' => $second->producer_id,
            'retailer_id' => $retailer->id,
            'created_at' => now()->subDay(),
        ]);
        PurchaseFactory::new()->create([
            'offer_id' => $first->id,
            'producer_id' => $first->producer_id,
            'retailer_id' => $retailer->id,
            'created_at' => now()->subDays(91),
        ]);

        $this->getJson("/v1/offers/compare?first_offer_id={$second->id}&second_offer_id={$first->id}")
            ->assertOk()
            ->assertJsonPath('data.offers.0.id', $second->id)
            ->assertJsonPath('data.offers.0.recent_sales', 1)
            ->assertJsonPath('data.offers.1.recent_sales', 0)
            ->assertJsonPath('data.criteria.recent_sales', $second->id)
            ->assertJsonPath('data.recommended_offer_id', null);

        $second->update(['source_product_id' => 'potato']);
        $this->getJson($url)->assertUnprocessable();

        $second->update(['status' => OfferStatus::INACTIVE]);
        $this->getJson($url)->assertNotFound();
    }
}
