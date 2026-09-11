<?php

namespace Database\Factories;

use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Purchase\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Purchase> */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        $offer = OfferFactory::new()->create([
            'status' => 'active',
        ]);
        $quantity = 1;

        return [
            'offer_id' => $offer->id,
            'retailer_id' => RetailerFactory::new(),
            'producer_id' => $offer->producer_id,
            'source_product_id' => $offer->source_product_id,
            'product_name' => $offer->product_name,
            'quantity' => $quantity,
            'unit_price' => $offer->unit_price,
            'total_price' => $offer->unit_price,
        ];
    }
}
