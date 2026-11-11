<?php

namespace Tests\Feature\domain\Models\HarvestForecast\Controllers\HarvestForecastController;

use Database\Factories\HarvestForecastFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_update_own_forecast_fields(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $forecast = HarvestForecastFactory::new()->create([
            'producer_id' => $producer->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);
        $endDate = now()->addDays(20)->toDateString();

        // Action
        $response = $this->patchJson('/v1/harvest-forecasts/'.$forecast->id, [
            'quantity' => 600,
            'end_date' => $endDate,
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.id', $forecast->id)
            ->assertJsonPath('data.quantity', 600)
            ->assertJsonPath('data.end_date', $endDate);
        $this->assertDatabaseHas('harvest_forecasts', [
            'id' => $forecast->id,
            'producer_id' => $producer->id,
            'quantity' => 600,
            'end_date' => $endDate,
        ]);
    }

    public function test_should_return_404_when_updating_another_producers_forecast(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $forecast = HarvestForecastFactory::new()->create(['producer_id' => $otherProducer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/harvest-forecasts/'.$forecast->id, ['quantity' => 600]);

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('harvest_forecasts', [
            'id' => $forecast->id,
            'producer_id' => $otherProducer->id,
            'quantity' => $forecast->quantity,
        ]);
    }

    public function test_should_reject_an_invalid_date_range_without_partial_update(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $forecast = HarvestForecastFactory::new()->create([
            'producer_id' => $producer->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'quantity' => 300,
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/harvest-forecasts/'.$forecast->id, [
            'quantity' => 600,
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        // Assert
        $response->assertUnprocessable();
        $this->assertDatabaseHas('harvest_forecasts', [
            'id' => $forecast->id,
            'quantity' => 300,
            'end_date' => now()->addDays(15)->toDateString(),
        ]);
    }

    public function test_should_allow_editing_an_active_forecast_whose_start_date_has_passed(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $forecast = HarvestForecastFactory::new()->create([
            'producer_id' => $producer->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/harvest-forecasts/'.$forecast->id, ['quantity' => 450]);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.quantity', 450)
            ->assertJsonPath('data.start_date', now()->subDay()->toDateString());
    }
}
