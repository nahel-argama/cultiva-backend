<?php

declare(strict_types=1);

namespace Tests\Feature\database\seeders;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\User\User;
use Cultiva\Models\Vehicle\GoLogVehicle;
use Database\Seeders\GoLogDriverSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GoLogDriverSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_seed_all_provisioned_golog_drivers_with_vehicles_idempotently(): void
    {
        // Arrange
        $sut = $this->app->make(GoLogDriverSeeder::class);

        // Action
        $sut->run();
        $sut->run();

        // Assert
        $this->assertSame(3, GoLogDriver::query()->count());
        $this->assertSame(3, GoLogVehicle::query()->count());

        $this->assertDatabaseHas('golog_drivers', [
            'cpf' => '52948173060',
            'golog_user_id' => '5c3d2b39-c067-4c0c-900d-2675f6c819d0',
            'golog_driver_id' => '7769e02b-87d9-475f-b400-6c4df77d14aa',
            'golog_work_schedule_id' => 'a6d6156e-39f8-4156-a208-d32e0a578def',
        ]);
        $this->assertDatabaseHas('golog_vehicles', [
            'golog_tractor_id' => 'c4bef783-d583-4c1f-9142-4bf6ee121596',
            'golog_equipament_group_id' => '5c0fd084-b358-4aae-aed0-2282963c26ca',
        ]);

        $this->assertDatabaseHas('golog_drivers', [
            'cpf' => '34729185005',
            'golog_user_id' => 'ae047dbf-71a1-42c9-8962-4d81458dd76d',
            'golog_driver_id' => 'bc936c40-f0c2-45a7-bb69-721a10888cd4',
            'golog_work_schedule_id' => '0b71d952-5754-48ca-982f-ebf5dc53d0ae',
        ]);
        $this->assertDatabaseHas('golog_vehicles', [
            'golog_tractor_id' => '52261194-1f73-4e3f-a4df-2bc6a9bf4f00',
            'golog_equipament_group_id' => '820c342d-3430-457b-8d4d-2f1fcbed8ff1',
        ]);

        $this->assertDatabaseHas('golog_drivers', [
            'cpf' => '28471936003',
            'golog_user_id' => '0cd342e7-64bb-42a2-be08-82796ef61495',
            'golog_driver_id' => '63a2dc1a-4362-453c-a753-20c42e30bb4a',
            'golog_work_schedule_id' => '3197e9b3-686a-41f0-8905-c60768db49be',
        ]);
        $this->assertDatabaseHas('golog_vehicles', [
            'golog_tractor_id' => 'a4fb1bf2-29f7-4aa5-ac93-1a24a6dec510',
            'golog_equipament_group_id' => 'c432a029-2c73-43fe-8ae8-b98fee04a8bb',
        ]);

        $drivers = GoLogDriver::query()->with(['company.user', 'company.address', 'vehicle'])->get();
        foreach ($drivers as $driver) {
            $this->assertSame(ProfileType::GOLOG_DRIVER, $driver->company->user->profile_type);
            $this->assertNotNull($driver->company->address);
            $this->assertSame(GoLogDriverStep::COMPLETED, $driver->getStoppedStep());
            $this->assertTrue($driver->isProvisioned());
        }

        $this->assertTrue(User::query()->where('email', 'henrique.costa@cultiva.com')->exists());
        $this->assertTrue(User::query()->where('email', 'camila.rocha@cultiva.com')->exists());
        $this->assertTrue(User::query()->where('email', 'thiago.almeida@cultiva.com')->exists());
    }
}
