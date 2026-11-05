<?php

namespace Tests\Feature\Database\Seeders;

use Database\Factories\UserFactory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_repeatable_demo_data_without_touching_other_users(): void
    {
        $otherUser = UserFactory::new()->create();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $producerIds = DB::table('producers')
            ->join('companies', 'companies.id', '=', 'producers.company_id')
            ->join('users', 'users.id', '=', 'companies.user_id')
            ->where('users.email', 'like', 'demo.producer.%@cultiva.test')
            ->pluck('producers.id');
        $retailerIds = DB::table('retailers')
            ->join('companies', 'companies.id', '=', 'retailers.company_id')
            ->join('users', 'users.id', '=', 'companies.user_id')
            ->where('users.email', 'like', 'demo.retailer.%@cultiva.test')
            ->pluck('retailers.id');

        $this->assertCount(25, $producerIds);
        $this->assertCount(25, $retailerIds);
        $this->assertSame(50, DB::table('users')->where('email', 'like', 'demo.%@cultiva.test')->count());
        $this->assertSame(200, DB::table('offers')->whereIn('producer_id', $producerIds)->count());
        $this->assertSame(250, DB::table('purchases')->whereIn('retailer_id', $retailerIds)->count());
        $this->assertSame(500, DB::table('wishlist_items')->whereIn('retailer_id', $retailerIds)->count());
        $this->assertSame(25, DB::table('purchases')->whereIn('producer_id', $producerIds)->distinct('producer_id')->count('producer_id'));
        $this->assertSame(25, DB::table('purchases')->whereIn('retailer_id', $retailerIds)->distinct('retailer_id')->count('retailer_id'));
        $this->assertSame(250, DB::table('purchases')
            ->join('offers', 'offers.id', '=', 'purchases.offer_id')
            ->whereIn('purchases.retailer_id', $retailerIds)
            ->where('offers.status', 'active')
            ->count());
        $this->assertSame(25, DB::table('purchases')
            ->select('retailer_id')
            ->whereIn('retailer_id', $retailerIds)
            ->groupBy('retailer_id')
            ->havingRaw('COUNT(*) = 10')
            ->get()
            ->count());
        $this->assertSame(25, DB::table('wishlist_items')
            ->select('retailer_id')
            ->whereIn('retailer_id', $retailerIds)
            ->groupBy('retailer_id')
            ->havingRaw('COUNT(*) = 20')
            ->get()
            ->count());
        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
    }
}
