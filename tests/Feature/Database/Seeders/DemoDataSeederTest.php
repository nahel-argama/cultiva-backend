<?php

namespace Tests\Feature\Database\Seeders;

use Database\Factories\UserFactory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_repeatable_demo_data_without_touching_other_users(): void
    {
        Storage::fake('public');
        $otherUser = UserFactory::new()->create();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $producerIds = DB::table('producers')
            ->join('companies', 'companies.id', '=', 'producers.company_id')
            ->join('users', 'users.id', '=', 'companies.user_id')
            ->where('users.email', 'like', 'produtor%@gmail.com')
            ->pluck('producers.id');
        $retailerIds = DB::table('retailers')
            ->join('companies', 'companies.id', '=', 'retailers.company_id')
            ->join('users', 'users.id', '=', 'companies.user_id')
            ->where('users.email', 'like', 'varejista%@gmail.com')
            ->pluck('retailers.id');

        $this->assertCount(25, $producerIds);
        $this->assertCount(50, $retailerIds);
        $this->assertSame(25, DB::table('users')->where('email', 'like', 'produtor%@gmail.com')->count());
        $this->assertSame(50, DB::table('users')->where('email', 'like', 'varejista%@gmail.com')->count());
        $this->assertSame(20, DB::table('offers')->whereIn('producer_id', $producerIds)
            ->select('source_product_id')->groupBy('source_product_id')
            ->havingRaw('COUNT(DISTINCT unit_price) > 1 AND COUNT(DISTINCT total_quantity) > 1')
            ->get()->count());
        $this->assertSame(300, DB::table('offers')->whereIn('producer_id', $producerIds)->count());
        $imagePaths = DB::table('offers')->whereIn('producer_id', $producerIds)->pluck('image_path');
        $this->assertGreaterThanOrEqual(200, $imagePaths->unique()->count());
        foreach ($imagePaths as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertSame(0, DB::table('offers')->whereIn('producer_id', $producerIds)
            ->whereNotBetween('unit_price', [1, 20])->count());
        $this->assertSame(0, DB::table('offers')->whereIn('producer_id', $producerIds)
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('wishlist_items')
                ->whereColumn('wishlist_items.source_product_id', 'offers.source_product_id')
                ->whereColumn('wishlist_items.product_name', 'offers.product_name'))
            ->count());
        $forecasts = DB::table('harvest_forecasts')->whereIn('producer_id', $producerIds);
        $this->assertSame(600, (clone $forecasts)->count());
        $this->assertTrue((clone $forecasts)->whereBetween('start_date', ['2026-10-01', '2026-10-31'])->exists());
        $this->assertTrue((clone $forecasts)->whereBetween('start_date', ['2026-11-01', '2026-11-30'])->exists());
        $this->assertGreaterThanOrEqual(300, (clone $forecasts)->whereDate('end_date', '>=', today())->count());

        $distances = DB::table('addresses')
            ->join('producers', 'producers.company_id', '=', 'addresses.company_id')
            ->whereIn('producers.id', $producerIds)
            ->selectRaw("ST_Distance(coordinate, 'POINT(-46.690 -23.550)'::geography) / 1000 AS distance_km")
            ->pluck('distance_km');
        $this->assertLessThan(5, $distances->min());
        $this->assertGreaterThan(100, $distances->max());
        $this->assertSame(375, DB::table('purchases')->whereIn('retailer_id', $retailerIds)->count());
        $this->assertSame(0, DB::table('purchases')->whereIn('retailer_id', $retailerIds)
            ->whereRaw('total_price != ROUND(unit_price * quantity * 1.05, 2)')->count());
        $this->assertSame(500, DB::table('wishlist_items')->whereIn('retailer_id', $retailerIds)->count());
        $this->assertSame(25, DB::table('purchases')->whereIn('producer_id', $producerIds)->distinct('producer_id')->count('producer_id'));
        $this->assertSame(25, DB::table('purchases')->whereIn('retailer_id', $retailerIds)->distinct('retailer_id')->count('retailer_id'));
        $this->assertSame(375, DB::table('purchases')
            ->join('offers', 'offers.id', '=', 'purchases.offer_id')
            ->whereIn('purchases.retailer_id', $retailerIds)
            ->where('offers.status', 'active')
            ->count());
        $this->assertSame(25, DB::table('purchases')
            ->select('retailer_id')
            ->whereIn('retailer_id', $retailerIds)
            ->groupBy('retailer_id')
            ->havingRaw('COUNT(*) = 15')
            ->get()
            ->count());
        $this->assertSame(50, DB::table('wishlist_items')->whereIn('retailer_id', $retailerIds)->distinct('retailer_id')->count('retailer_id'));
        $this->assertSame([50, 42, 38, 34, 30, 28, 26, 24, 22, 19, 19, 19, 19, 19, 19, 19, 19, 18, 18, 18], DB::table('wishlist_items')
            ->selectRaw('COUNT(*) as total')
            ->whereIn('retailer_id', $retailerIds)
            ->groupBy('source_product_id')
            ->orderByDesc('total')
            ->pluck('total')
            ->all());
        $this->assertSame(50, DB::table('wishlist_items')
            ->select('retailer_id')
            ->whereIn('retailer_id', $retailerIds)
            ->groupBy('retailer_id')
            ->havingRaw('COUNT(*) = 10')
            ->get()
            ->count());
        foreach (DB::table('wishlist_items')->distinct()->pluck('source_product_id') as $productId) {
            Storage::disk('public')->assertExists("products/{$productId}.jpg");
        }
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }
}
