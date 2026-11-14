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

final class StoreWorkScheduleTest extends TestCase
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

    public function test_should_create_work_schedule_for_provisioned_driver(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
            'golog_equipament_group_id' => '44444444-5555-6666-7777-888888888888',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $schedulesFixture = json_decode((string) require base_path('tests/Fixtures/Integrations/GoLog/list_work_schedules_success.php'), true);
        $schedulesFixture[] = [
            ...$schedulesFixture[0],
            'id' => '66666666-6666-6666-6666-666666666666',
            'scheduleDate' => '2026-11-11',
            'startWorkday' => '09:00:00',
            'endWorkday' => '19:00:00',
        ];

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/workSchedule' => fn ($request) => $request->method() === 'POST'
                ? Http::response('', 201)
                : Http::response($schedulesFixture, 200),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'schedule_date' => '2026-11-10',
            'start_time' => '08:00',
            'end_time' => '18:00',
        ];

        // Action
        $response = $this->postJson('/v1/golog/work-schedules', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonMissingPath('data.golog_work_schedule_id')
            ->assertJsonPath('data.stopped_step', 'completed')
            ->assertJsonPath('data.is_provisioned', true);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://golog.test/workSchedule'
            && $request->method() === 'POST'
            && $request['driverId'] === '22222222-3333-4444-5555-666666666666'
            && $request['equipamentGroupId'] === '44444444-5555-6666-7777-888888888888'
            && $request['scheduleDate'] === '2026-11-10'
            && $request['startWorkday'] === '08:00:00'
            && $request['endWorkday'] === '18:00:00');

        $driver->refresh();
        $this->assertSame(GoLogDriverStep::COMPLETED, $driver->getStoppedStep());
        $this->assertTrue($driver->isProvisioned());
    }

    public function test_should_allow_creating_multiple_work_schedules_for_provisioned_driver(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
            'golog_equipament_group_id' => '44444444-5555-6666-7777-888888888888',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $schedulesFixture = json_decode((string) require base_path('tests/Fixtures/Integrations/GoLog/list_work_schedules_success.php'), true);
        $schedulesFixture[] = [
            ...$schedulesFixture[0],
            'id' => '66666666-6666-6666-6666-666666666666',
            'scheduleDate' => '2026-11-11',
            'startWorkday' => '09:00:00',
            'endWorkday' => '19:00:00',
        ];

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/workSchedule' => fn ($request) => $request->method() === 'POST'
                ? Http::response('', 201)
                : Http::response($schedulesFixture, 200),
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        // Action
        $firstResponse = $this->postJson('/v1/golog/work-schedules', [
            'schedule_date' => '2026-11-10',
            'start_time' => '08:00',
            'end_time' => '18:00',
        ]);

        $secondResponse = $this->postJson('/v1/golog/work-schedules', [
            'schedule_date' => '2026-11-11',
            'start_time' => '09:00',
            'end_time' => '19:00',
        ]);

        // Assert
        $firstResponse->assertStatus(201);
        $secondResponse->assertStatus(201);

        Http::assertSent(fn ($request): bool => $request->url() === 'http://golog.test/workSchedule'
            && $request->method() === 'POST'
            && $request['scheduleDate'] === '2026-11-10');
        Http::assertSent(fn ($request): bool => $request->url() === 'http://golog.test/workSchedule'
            && $request->method() === 'POST'
            && $request['scheduleDate'] === '2026-11-11');
    }

    public function test_should_return_422_when_equipament_group_was_not_created_yet(): void
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
            'golog_equipament_group_id' => null,
        ]);

        Sanctum::actingAs($driver->company->user, ['access']);

        $payload = [
            'schedule_date' => '2026-11-10',
            'start_time' => '08:00',
            'end_time' => '18:00',
        ];

        // Action
        $response = $this->postJson('/v1/golog/work-schedules', $payload);

        // Assert
        $response->assertStatus(422);
        Http::assertNothingSent();
    }
}
