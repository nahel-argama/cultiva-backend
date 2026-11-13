<?php

namespace Database\Seeders;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Purchase\Purchase;
use Cultiva\Models\Retailer\Retailer;
use Database\Factories\CompanyFactory;
use Database\Factories\HarvestForecastFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\UserFactory;
use Database\Factories\WishlistItemFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'teste@123';

    private const PRODUCTS = [
        'Frutas' => ['Abacate', 'Banana', 'Goiaba', 'Laranja', 'Limão', 'Mamão', 'Manga', 'Uva'],
        'Legumes' => ['Abóbora', 'Berinjela', 'Brócolis', 'Cebola', 'Cenoura', 'Pepino', 'Tomate'],
        'Verduras' => ['Alface', 'Couve'],
        'Tubérculos e raízes' => ['Batata Doce', 'Mandioca'],
        'Grãos e cereais' => ['Feijão'],
    ];

    private const PRODUCT_IDS = [
        'Abacate' => '116',
        'Banana' => '642',
        'Goiaba' => '84',
        'Laranja' => '188',
        'Limão' => '405',
        'Mamão' => '492',
        'Manga' => '75',
        'Uva' => '241',
        'Abóbora' => '136',
        'Berinjela' => '58',
        'Brócolis' => '100',
        'Cebola' => '151',
        'Cenoura' => '94',
        'Pepino' => '60',
        'Tomate' => '238',
        'Alface' => '46',
        'Couve' => '88',
        'Batata Doce' => '99',
        'Mandioca' => '71',
        'Feijão' => '9',
    ];

    private const PRODUCT_PRICES = [
        'Abacate' => '4.75',
        'Banana' => '5.00',
        'Goiaba' => '6.50',
        'Laranja' => '3.75',
        'Limão' => '7.90',
        'Mamão' => '6.50',
        'Manga' => '3.90',
        'Uva' => '10.00',
        'Abóbora' => '3.00',
        'Berinjela' => '4.20',
        'Brócolis' => '8.00',
        'Cebola' => '7.25',
        'Cenoura' => '2.50',
        'Pepino' => '4.00',
        'Tomate' => '10.00',
        'Alface' => '5.70',
        'Couve' => '6.00',
        'Batata Doce' => '4.00',
        'Mandioca' => '4.50',
        'Feijão' => '8.00',
    ];

    private const WISHLIST_COUNTS = [50, 42, 38, 34, 30, 28, 26, 24, 22, 19, 19, 19, 19, 19, 19, 19, 19, 18, 18, 18];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo data can only be seeded outside production.');
        }

        DB::transaction(function (): void {
            $this->removeExistingDemoData();

            $categories = Category::query()->pluck('id', 'name')->all();
            $producers = $this->createProducers();
            $retailers = $this->createRetailers();
            $offers = $this->createOffers($producers, $categories);
            $this->createHarvestForecasts($offers);

            $this->createPurchases(array_slice($retailers, 0, 25), $offers);
            $this->createWishlistItems($retailers);
        });

        foreach (self::PRODUCT_IDS as $productId) {
            $path = "products/{$productId}.jpg";
            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, file_get_contents(database_path("seeders/images/{$path}")));
            }
        }

        foreach (glob(database_path('seeders/images/demo-offers/*.jpg')) as $image) {
            Storage::disk('public')->put('demo-offers/'.basename($image), file_get_contents($image));
        }
    }

    private function removeExistingDemoData(): void
    {
        $seedEmails = [
            ...array_map(static fn (int $number): string => sprintf('produtor%d@gmail.com', $number), range(1, 25)),
            ...array_map(static fn (int $number): string => sprintf('varejista%d@gmail.com', $number), range(1, 50)),
        ];
        $userIds = DB::table('users')
            ->where('email', 'like', 'demo.%@cultiva.test')
            ->orWhereIn('email', $seedEmails)
            ->pluck('id');
        $companyIds = DB::table('companies')->whereIn('user_id', $userIds)->pluck('id');
        $producerIds = DB::table('producers')->whereIn('company_id', $companyIds)->pluck('id');
        $retailerIds = DB::table('retailers')->whereIn('company_id', $companyIds)->pluck('id');

        $purchaseIds = DB::table('purchases')
            ->whereIn('producer_id', $producerIds)
            ->orWhereIn('retailer_id', $retailerIds)
            ->pluck('id');
        DB::table('delivery_orders')->whereIn('purchase_id', $purchaseIds)->delete();
        DB::table('purchases')->whereIn('id', $purchaseIds)->delete();
        DB::table('offers')->whereIn('producer_id', $producerIds)->delete();
        DB::table('wishlist_items')->whereIn('retailer_id', $retailerIds)->delete();
        DB::table('producers')->whereIn('id', $producerIds)->delete();
        DB::table('retailers')->whereIn('id', $retailerIds)->delete();
        DB::table('companies')->whereIn('id', $companyIds)->delete();
        DB::table('users')->whereIn('id', $userIds)->delete();
    }

    /** @return list<Producer> */
    private function createProducers(): array
    {
        $producers = [];

        foreach (range(1, 25) as $number) {
            $user = UserFactory::new()->create([
                'email' => sprintf('produtor%d@gmail.com', $number),
                'password' => self::DEMO_PASSWORD,
                'profile_type' => ProfileType::PRODUCER,
            ]);
            $company = CompanyFactory::new()->create(['user_id' => $user->id]);
            $producers[] = ProducerFactory::new()->create(['company_id' => $company->id]);
        }

        return $producers;
    }

    /** @return list<Retailer> */
    private function createRetailers(): array
    {
        $retailers = [];

        foreach (range(1, 50) as $number) {
            $user = UserFactory::new()->retailer()->create([
                'email' => sprintf('varejista%d@gmail.com', $number),
                'password' => self::DEMO_PASSWORD,
            ]);
            $company = CompanyFactory::new()->create(['user_id' => $user->id]);
            $retailers[] = RetailerFactory::new()->create(['company_id' => $company->id]);
        }

        return $retailers;
    }

    /** @param list<Producer> $producers
     * @param  array<string, int>  $categories
     * @return list<Offer>
     */
    private function createOffers(array $producers, array $categories): array
    {
        $offers = [];

        foreach ($producers as $producerIndex => $producer) {
            foreach (range(1, 8) as $offerNumber) {
                $categoryName = array_keys(self::PRODUCTS)[($producerIndex * 8 + $offerNumber - 1) % count(self::PRODUCTS)];
                $products = self::PRODUCTS[$categoryName];
                $productName = $products[($producerIndex + $offerNumber - 1) % count($products)];
                $offers[] = OfferFactory::new()->create([
                    'producer_id' => $producer->id,
                    'category_id' => $categories[$categoryName],
                    'source_product_id' => self::PRODUCT_IDS[$productName],
                    'product_name' => $productName,
                    'image_path' => 'demo-offers/'.($producerIndex + 1)."-{$offerNumber}.jpg",
                    'unit_price' => self::PRODUCT_PRICES[$productName],
                    'total_quantity' => 100,
                    'reserved_quantity' => 0,
                    'status' => OfferStatus::ACTIVE,
                    'harvest_date' => now()->toDateString(),
                ]);
            }
        }

        return $offers;
    }

    /** @param list<Offer> $offers */
    private function createHarvestForecasts(array $offers): void
    {
        foreach ($offers as $index => $offer) {
            $startDate = now()->addDays(7 + ($index % 45));

            HarvestForecastFactory::new()->create([
                'producer_id' => $offer->producer_id,
                'source_product_id' => $offer->source_product_id,
                'product_name' => $offer->product_name,
                'quantity' => 100 + ($index % 5) * 50,
                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate->copy()->addDays($index % 3 === 0 ? 14 : 0)->toDateString(),
            ]);
        }
    }

    /** @param list<Retailer> $retailers
     * @param  list<Offer>  $offers
     */
    private function createPurchases(array $retailers, array $offers): void
    {
        foreach ($retailers as $retailerIndex => $retailer) {
            foreach (range(0, 9) as $purchaseIndex) {
                $offer = $offers[($retailerIndex * 10 + $purchaseIndex) % count($offers)];
                $quantity = 1 + (($retailerIndex + $purchaseIndex) % 5);

                $subtotalCents = (int) round((float) $offer->unit_price * 100) * $quantity;

                Purchase::query()->create([
                    'offer_id' => $offer->id,
                    'retailer_id' => $retailer->id,
                    'producer_id' => $offer->producer_id,
                    'source_product_id' => $offer->source_product_id,
                    'product_name' => $offer->product_name,
                    'quantity' => $quantity,
                    'unit_price' => $offer->unit_price,
                    'total_price' => ($subtotalCents + (int) round($subtotalCents * 0.05)) / 100,
                ]);

                $offer->increment('reserved_quantity', $quantity);
            }
        }
    }

    /** @param list<Retailer> $retailers */
    private function createWishlistItems(array $retailers): void
    {
        $products = array_merge(...array_values(self::PRODUCTS));
        $retailerOffset = 0;

        foreach ($products as $productIndex => $productName) {
            foreach (range(0, self::WISHLIST_COUNTS[$productIndex] - 1) as $offset) {
                $retailerIndex = ($retailerOffset + $offset) % count($retailers);
                WishlistItemFactory::new()->create([
                    'retailer_id' => $retailers[$retailerIndex]->id,
                    'source_product_id' => self::PRODUCT_IDS[$productName],
                    'product_name' => $productName,
                ]);
            }

            $retailerOffset += self::WISHLIST_COUNTS[$productIndex];
        }
    }
}
