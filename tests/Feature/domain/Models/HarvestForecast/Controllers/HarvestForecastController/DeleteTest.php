<?php

namespace Tests\Feature\domain\Models\HarvestForecast\Controllers\HarvestForecastController;

use Database\Factories\HarvestForecastFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_remove_own_forecast(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $forecast = HarvestForecastFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->deleteJson('/v1/harvest-forecasts/'.$forecast->id);

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing('harvest_forecasts', ['id' => $forecast->id]);
    }

    public function test_should_not_remove_another_producers_forecast(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $forecast = HarvestForecastFactory::new()->create(['producer_id' => $otherProducer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->deleteJson('/v1/harvest-forecasts/'.$forecast->id);

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('harvest_forecasts', ['id' => $forecast->id]);
    }
}
