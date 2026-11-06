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

final class StoreEquipamentGroupTest extends TestCase
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

    public function test_should_allocate_vehicle_into_equipament_group_and_advance_step_to_work_schedule(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);
        $vehicle = GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
            'golog_equipament_group_id' => null,
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $groupFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_equipament_group_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/equipament-group' => Http::response($groupFixture, 200),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'observation' => 'Conjunto padrão motorista',
        ];

        // Action
        $response = $this->postJson('/v1/golog/equipament-groups', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.vehicle.golog_tractor_id', '33333333-4444-5555-6666-777777777777')
            ->assertJsonPath('data.vehicle.golog_equipament_group_id', '44444444-5555-6666-7777-888888888888')
            ->assertJsonPath('data.stopped_step', 'work_schedule')
            ->assertJsonPath('data.is_provisioned', false);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://golog.test/equipament-group'
            && $request['equipament1Id'] === $vehicle->golog_tractor_id
            && $request['observation'] === 'Conjunto padrão motorista');

        $this->assertDatabaseHas('golog_vehicles', [
            'id' => $vehicle->id,
            'golog_equipament_group_id' => '44444444-5555-6666-7777-888888888888',
        ]);

        $driver->refresh();
        $this->assertSame(GoLogDriverStep::WORK_SCHEDULE, $driver->getStoppedStep());
    }

    public function test_should_return_422_when_vehicle_was_not_created_yet(): void
    {
        // Arrange
        Http::fake();

        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/golog/equipament-groups', []);

        // Assert
        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_should_return_409_when_equipament_group_already_exists(): void
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
            'golog_equipament_group_id' => '44444444-5555-6666-7777-888888888888',
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/golog/equipament-groups', []);

        // Assert
        $response->assertStatus(409);
        Http::assertNothingSent();
    }
}
