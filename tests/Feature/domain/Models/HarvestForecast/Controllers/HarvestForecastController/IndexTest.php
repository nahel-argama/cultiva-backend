<?php

namespace Tests\Feature\domain\Models\HarvestForecast\Controllers\HarvestForecastController;

use Database\Factories\HarvestForecastFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_filter_future_forecasts_and_show_producer_state(): void
    {
        // Arrange
        $matching = HarvestForecastFactory::new()->create([
            'source_product_id' => '0002',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        $matching->producer->company->update(['trade_name' => 'Sítio Cultiva']);
        $matching->producer->company->address->update(['state' => 'SP']);
        HarvestForecastFactory::new()->create([
            'source_product_id' => '0003',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        HarvestForecastFactory::new()->create([
            'source_product_id' => '0002',
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
        ]);
        HarvestForecastFactory::new()->create([
            'source_product_id' => '0002',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/harvest-forecasts?source_product_id=0002&date_from='
            .now()->addDays(7)->toDateString().'&date_to='.now()->addDays(12)->toDateString());

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('data.0.product.source_product_id', '0002')
            ->assertJsonPath('data.0.producer.name', 'Sítio Cultiva')
            ->assertJsonPath('data.0.producer.state', 'SP');
    }

    public function test_should_paginate_future_forecasts(): void
    {
        // Arrange
        HarvestForecastFactory::new()->create([
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);
        HarvestForecastFactory::new()->create([
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
        ]);
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/harvest-forecasts?per_page=1&page=2');

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_producer_should_only_see_own_forecasts(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $ownForecast = HarvestForecastFactory::new()->create(['producer_id' => $producer->id]);
        $otherProducer = ProducerFactory::new()->create();
        HarvestForecastFactory::new()->create(['producer_id' => $otherProducer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/harvest-forecasts');

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $ownForecast->id);
    }

    public function test_should_reject_invalid_pagination(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/harvest-forecasts?per_page=101&page=0');

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'per_page']);
    }
}
