<?php

namespace Tests\Feature\domain\Models\HarvestForecast\Controllers\HarvestForecastController;

use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeasonalPeriodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_general_season_period_for_product(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        config(['harvest_seasons' => [
            'Tomate Italiano' => [[
                'start_month' => 1,
                'end_month' => 3,
                'source' => 'Calendário de referência Cultiva',
            ]],
        ]]);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');
        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/harvest-season-periods?source_product_id=0002');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.source_product_id', '0002')
            ->assertJsonPath('data.0.product_name', 'Tomate Italiano')
            ->assertJsonPath('data.0.start_month', 1)
            ->assertJsonPath('data.0.end_month', 3);
    }
}
