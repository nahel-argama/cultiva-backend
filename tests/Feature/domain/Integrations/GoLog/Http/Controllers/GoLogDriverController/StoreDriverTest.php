<?php

declare(strict_types=1);

namespace Tests\Feature\domain\Integrations\GoLog\Http\Controllers\GoLogDriverController;

use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Database\Factories\GoLogDriverFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StoreDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.golog.base_url' => 'http://golog.test',
            'services.golog.email' => 'admin@golog.test',
            'services.golog.password' => 'secret123',
            'services.golog.timeout' => 5,
        ]);
    }

    public function test_should_create_golog_driver_and_advance_step_to_tractor(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'cpf' => '40374708029',
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => null,
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $driverFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_driver_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/driver' => Http::response($driverFixture, 200),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'cnh_number' => '12345678901',
            'cnh_expiration' => '2028-12-31',
            'cost_per_hour' => 45.0,
        ];

        // Action
        $response = $this->postJson('/v1/golog/drivers', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.golog_user_id', '11111111-2222-3333-4444-555555555555')
            ->assertJsonPath('data.golog_driver_id', '22222222-3333-4444-5555-666666666666')
            ->assertJsonPath('data.stopped_step', 'tractor')
            ->assertJsonPath('data.is_provisioned', false);

        $this->assertDatabaseHas('golog_drivers', [
            'id' => $driver->id,
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        $driver->refresh();
        $this->assertSame(GoLogDriverStep::TRACTOR, $driver->getStoppedStep());
    }

    public function test_should_keep_user_step_intact_when_golog_driver_request_fails(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => null,
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/driver' => Http::response(['message' => 'GoLog error'], 500),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'cnh_number' => '12345678901',
            'cnh_expiration' => '2028-12-31',
            'cost_per_hour' => 45.0,
        ];

        // Action
        $response = $this->postJson('/v1/golog/drivers', $payload);

        // Assert
        $response->assertStatus(500);

        $driver->refresh();
        $this->assertSame('11111111-2222-3333-4444-555555555555', $driver->golog_user_id);
        $this->assertNull($driver->golog_driver_id);
        $this->assertSame(GoLogDriverStep::DRIVER, $driver->getStoppedStep());
    }

    public function test_should_return_409_when_golog_driver_already_exists(): void
    {
        // Arrange
        Http::fake();

        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'cnh_number' => '12345678901',
            'cnh_expiration' => '2028-12-31',
            'cost_per_hour' => 45.0,
        ];

        // Action
        $response = $this->postJson('/v1/golog/drivers', $payload);

        // Assert
        $response->assertStatus(409);
        Http::assertNothingSent();
    }
}
