<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Actions\ResolveAvailableWorkSchedulesAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\AvailableWorkSchedulesDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogWorkScheduleResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogWorkScheduleStatus;
use Database\Factories\GoLogDriverFactory;
use Database\Factories\GoLogVehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ResolveAvailableWorkSchedulesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_select_earliest_active_schedule_date_on_or_after_next_weekday_for_provisioned_drivers(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 11:00:00', 'America/Sao_Paulo'));

        $driver1 = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a1111111-1111-1111-1111-111111111111',
            'golog_driver_id' => 'b1111111-1111-1111-1111-111111111111',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver1->id,
            'golog_tractor_id' => 'c1111111-1111-1111-1111-111111111111',
            'golog_equipament_group_id' => 'e1111111-1111-1111-1111-111111111111',
        ]);

        $driver2 = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a2222222-2222-2222-2222-222222222222',
            'golog_driver_id' => 'b2222222-2222-2222-2222-222222222222',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver2->id,
            'golog_tractor_id' => 'c2222222-2222-2222-2222-222222222222',
            'golog_equipament_group_id' => 'e2222222-2222-2222-2222-222222222222',
        ]);

        // Unprovisioned driver (missing vehicle provisioning)
        GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a3333333-3333-3333-3333-333333333333',
            'golog_driver_id' => 'b3333333-3333-3333-3333-333333333333',
        ]);

        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listActiveWorkSchedules')
                ->once()
                ->andReturn([
                    new GoLogWorkScheduleResponseDTO(
                        id: 'past-schedule',
                        scheduleDate: '2026-11-13',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b1111111-1111-1111-1111-111111111111',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'unprovisioned-driver-next-weekday',
                        scheduleDate: '2026-11-16',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b3333333-3333-3333-3333-333333333333',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'external-driver-next-weekday',
                        scheduleDate: '2026-11-16',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b9999999-9999-9999-9999-999999999999',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'later-day-schedule',
                        scheduleDate: '2026-11-18',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b1111111-1111-1111-1111-111111111111',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'disabled-earliest-day',
                        scheduleDate: '2026-11-17',
                        status: GoLogWorkScheduleStatus::DESATIVADO,
                        driverId: 'b1111111-1111-1111-1111-111111111111',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'c117-1111-1111-1111-111111111111',
                        scheduleDate: '2026-11-17',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b1111111-1111-1111-1111-111111111111',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'c217-2222-2222-2222-222222222222',
                        scheduleDate: '2026-11-17',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b2222222-2222-2222-2222-222222222222',
                    ),
                ]);
        });

        $sut = new ResolveAvailableWorkSchedulesAction($clientMock);

        // Action
        try {
            $result = $sut->execute();
        } finally {
            CarbonImmutable::setTestNow();
        }

        // Assert
        $this->assertInstanceOf(AvailableWorkSchedulesDTO::class, $result);
        $this->assertSame('2026-11-17', $result->scheduleDate->toDateString());
        $this->assertSame('America/Sao_Paulo', $result->scheduleDate->timezoneName);
        $this->assertSame([
            'c117-1111-1111-1111-111111111111',
            'c217-2222-2222-2222-222222222222',
        ], $result->workScheduleIds);
    }

    public function test_should_throw_exception_when_no_provisioned_drivers_exist(): void
    {
        // Arrange
        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a1111111-1111-1111-1111-111111111111',
            'golog_driver_id' => 'b1111111-1111-1111-1111-111111111111',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => 'c1111111-1111-1111-1111-111111111111',
            'golog_equipament_group_id' => null,
        ]);

        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('listActiveWorkSchedules');
        });

        $sut = new ResolveAvailableWorkSchedulesAction($clientMock);

        // Action & Assert
        try {
            $sut->execute();
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertStringContainsString('provisioned GoLog drivers', $exception->getMessage());
        }
    }

    public function test_should_throw_exception_when_no_active_work_schedules_exist_from_next_weekday_onward(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-15 11:00:00', 'America/Sao_Paulo'));

        $driver = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a1111111-1111-1111-1111-111111111111',
            'golog_driver_id' => 'b1111111-1111-1111-1111-111111111111',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => 'c1111111-1111-1111-1111-111111111111',
            'golog_equipament_group_id' => 'e1111111-1111-1111-1111-111111111111',
        ]);

        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('listActiveWorkSchedules')
                ->once()
                ->andReturn([
                    new GoLogWorkScheduleResponseDTO(
                        id: 'past-schedule',
                        scheduleDate: '2026-11-13',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b1111111-1111-1111-1111-111111111111',
                    ),
                    new GoLogWorkScheduleResponseDTO(
                        id: 'external-driver-schedule',
                        scheduleDate: '2026-11-17',
                        status: GoLogWorkScheduleStatus::ATIVO,
                        driverId: 'b9999999-9999-9999-9999-999999999999',
                    ),
                ]);
        });

        $sut = new ResolveAvailableWorkSchedulesAction($clientMock);

        // Action & Assert
        try {
            $sut->execute();
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertStringContainsString('2026-11-16', $exception->getMessage());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
