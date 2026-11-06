<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Models\Delivery;

use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\User\User;
use Cultiva\Models\Vehicle\GoLogVehicle;
use Database\Factories\CompanyFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;

class GoLogDriverStepperTest extends TestCase
{
    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::new()->create();
        $this->company = CompanyFactory::new()->create(['user_id' => $this->user->id]);
    }

    public function test_should_return_user_step_when_golog_user_id_is_null(): void
    {
        $driver = new GoLogDriver();

        $this->assertSame(GoLogDriverStep::USER, $driver->getStoppedStep());
        $this->assertFalse($driver->isProvisioned());
    }

    public function test_should_return_driver_step_when_only_golog_user_id_is_present(): void
    {
        $driver = new GoLogDriver([
            'golog_user_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $this->assertSame(GoLogDriverStep::DRIVER, $driver->getStoppedStep());
        $this->assertFalse($driver->isProvisioned());
    }

    public function test_should_return_tractor_step_when_user_and_driver_ids_are_present_without_vehicle_tractor(): void
    {
        $driver = GoLogDriver::query()->create([
            'company_id' => $this->company->id,
            'cpf' => '40374708029',
            'golog_user_id' => '11111111-1111-1111-1111-111111111111',
            'golog_driver_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        $this->assertSame(GoLogDriverStep::TRACTOR, $driver->getStoppedStep());
        $this->assertFalse($driver->isProvisioned());
    }

    public function test_should_return_equipament_group_step_when_tractor_is_present_without_group(): void
    {
        $driver = GoLogDriver::query()->create([
            'company_id' => $this->company->id,
            'cpf' => '40374708029',
            'golog_user_id' => '11111111-1111-1111-1111-111111111111',
            'golog_driver_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        GoLogVehicle::query()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-3333-3333-3333-333333333333',
        ]);

        $driver->load('vehicle');

        $this->assertSame(GoLogDriverStep::EQUIPAMENT_GROUP, $driver->getStoppedStep());
        $this->assertFalse($driver->isProvisioned());
    }

    public function test_should_return_work_schedule_step_when_group_is_present_without_schedule(): void
    {
        $driver = GoLogDriver::query()->create([
            'company_id' => $this->company->id,
            'cpf' => '40374708029',
            'golog_user_id' => '11111111-1111-1111-1111-111111111111',
            'golog_driver_id' => '22222222-2222-2222-2222-222222222222',
        ]);

        GoLogVehicle::query()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-3333-3333-3333-333333333333',
            'golog_equipament_group_id' => '44444444-4444-4444-4444-444444444444',
        ]);

        $driver->load('vehicle');

        $this->assertSame(GoLogDriverStep::WORK_SCHEDULE, $driver->getStoppedStep());
        $this->assertFalse($driver->isProvisioned());
    }

    public function test_should_return_completed_when_all_golog_ids_are_present(): void
    {
        $driver = GoLogDriver::query()->create([
            'company_id' => $this->company->id,
            'cpf' => '40374708029',
            'golog_user_id' => '11111111-1111-1111-1111-111111111111',
            'golog_driver_id' => '22222222-2222-2222-2222-222222222222',
            'golog_work_schedule_id' => '55555555-5555-5555-5555-555555555555',
        ]);

        GoLogVehicle::query()->create([
            'golog_driver_id' => $driver->id,
            'golog_tractor_id' => '33333333-3333-3333-3333-333333333333',
            'golog_equipament_group_id' => '44444444-4444-4444-4444-444444444444',
        ]);

        $driver->load('vehicle');

        $this->assertSame(GoLogDriverStep::COMPLETED, $driver->getStoppedStep());
        $this->assertTrue($driver->isProvisioned());
    }
}
