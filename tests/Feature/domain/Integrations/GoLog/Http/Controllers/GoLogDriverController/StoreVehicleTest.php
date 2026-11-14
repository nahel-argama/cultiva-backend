<?php

declare(strict_types=1);

namespace Tests\Feature\domain\Integrations\GoLog\Http\Controllers\GoLogDriverController;

use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Database\Factories\GoLogDriverFactory;
use Database\Factories\GoLogVehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class StoreVehicleTest extends TestCase
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

    public function test_should_create_golog_tractor_and_advance_step_to_equipament_group(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $tractorFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_tractor_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/tractor' => Http::response($tractorFixture, 200),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'plate' => 'BRA2E19',
            'capacity' => 1500.5,
            'renavam' => '12345678901',
            'model' => 'Mercedes Sprinter',
            'number_axles' => 2,
            'type_fuel' => 'DIESEL',
            'cost_per_kilometer' => 2.5,
        ];

        // Action
        $response = $this->postJson('/v1/golog/vehicles', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.vehicle.golog_tractor_id', '33333333-4444-5555-6666-777777777777')
            ->assertJsonPath('data.vehicle.golog_equipament_group_id', null)
            ->assertJsonPath('data.stopped_step', 'equipament_group')
            ->assertJsonPath('data.is_provisioned', false);

        Http::assertNotSent(fn ($request): bool => $request->url() === 'http://golog.test/equipament-group');

        $this->assertDatabaseHas('golog_vehicles', [
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
            'golog_equipament_group_id' => null,
        ]);

        $driver->refresh();
        $this->assertSame(GoLogDriverStep::EQUIPAMENT_GROUP, $driver->getStoppedStep());
    }

    public function test_should_create_rigid_vehicle_with_optional_metrics_and_auto_resolve_equipament_group(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $tractorFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_tractor_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/tractor' => Http::response($tractorFixture, 200),
            'http://golog.test/equipament-group' => Http::response([
                [
                    'id' => '44444444-5555-6666-7777-888888888888',
                    'equipament1' => ['id' => '33333333-4444-5555-6666-777777777777'],
                    'observation' => 'Conjunto automático',
                ],
            ], 200),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'plate' => 'BRA2E19',
            'capacity' => 1500.5,
            'renavam' => '12345678901',
            'model' => 'Mercedes Sprinter',
            'number_axles' => 2,
            'type_fuel' => 'FLEX',
            'cost_per_kilometer' => 2.5,
            'vehicle_type' => 'VUC',
            'cargo_volume' => 12.5,
            'km_per_liter' => 7.8,
        ];

        // Action
        $response = $this->postJson('/v1/golog/vehicles', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.vehicle.golog_tractor_id', '33333333-4444-5555-6666-777777777777')
            ->assertJsonPath('data.vehicle.golog_equipament_group_id', '44444444-5555-6666-7777-888888888888')
            ->assertJsonPath('data.stopped_step', 'work_schedule')
            ->assertJsonPath('data.is_provisioned', false);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://golog.test/tractor'
            && $request['vehicleType'] === 'VUC'
            && $request['cargoVolume'] == 12.5
            && $request['kmPerLiter'] == 7.8
            && $request['typeFuel'] === 'FLEX'
            && ! array_key_exists('Type Fuel', $request->data()));

        $this->assertDatabaseHas('golog_vehicles', [
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
            'golog_equipament_group_id' => '44444444-5555-6666-7777-888888888888',
        ]);

        $driver->refresh();
        $this->assertSame(GoLogDriverStep::WORK_SCHEDULE, $driver->getStoppedStep());
    }

    public function test_should_not_create_vehicle_record_when_golog_tractor_request_fails(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/tractor' => Http::response(['message' => 'Tractor error'], 500),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'plate' => 'BRA2E19',
            'capacity' => 1500.5,
            'renavam' => '12345678901',
            'model' => 'Mercedes Sprinter',
            'number_axles' => 2,
            'type_fuel' => 'DIESEL',
            'cost_per_kilometer' => 2.5,
        ];

        // Action
        $response = $this->postJson('/v1/golog/vehicles', $payload);

        // Assert
        $response->assertStatus(500);
        $this->assertDatabaseCount('golog_vehicles', 0);
    }

    public function test_should_return_422_when_driver_step_was_not_completed_yet(): void
    {
        // Arrange
        Http::fake();

        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => null,
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'plate' => 'BRA2E19',
            'capacity' => 1500.5,
            'renavam' => '12345678901',
            'model' => 'Mercedes Sprinter',
            'number_axles' => 2,
            'type_fuel' => 'DIESEL',
            'cost_per_kilometer' => 2.5,
        ];

        // Action
        $response = $this->postJson('/v1/golog/vehicles', $payload);

        // Assert
        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_should_return_409_when_vehicle_already_exists(): void
    {
        // Arrange
        Http::fake();

        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'plate' => 'BRA2E19',
            'capacity' => 1500.5,
            'renavam' => '12345678901',
            'model' => 'Mercedes Sprinter',
            'number_axles' => 2,
            'type_fuel' => 'DIESEL',
            'cost_per_kilometer' => 2.5,
        ];

        // Action
        $response = $this->postJson('/v1/golog/vehicles', $payload);

        // Assert
        $response->assertStatus(409);
        Http::assertNothingSent();
    }
}
