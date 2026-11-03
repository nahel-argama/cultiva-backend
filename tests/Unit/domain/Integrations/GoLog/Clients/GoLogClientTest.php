<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Clients;

use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogAuthResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogDriverResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogEquipamentGroupResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTractorResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogUserResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoLogClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.golog.base_url' => 'http://golog.test',
            'services.golog.email' => 'admin@golog.test',
            'services.golog.password' => 'secret123',
            'services.golog.timeout' => 5,
        ]);
    }

    public function test_should_authenticate_and_cache_jwt_token_and_company_id(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $authDto = $sut->login();
        $token = $sut->getToken();
        $companyId = $sut->getCompanyId();
        $token2 = $sut->getToken();

        // Assert
        $this->assertInstanceOf(GoLogAuthResponseDTO::class, $authDto);
        $this->assertSame('mock-golog-jwt-token', $authDto->token);
        $this->assertSame('a0000000-0000-0000-0000-000000000001', $authDto->userId);
        $this->assertSame(GoLogUserProfile::ADMIN, $authDto->userRole);
        $this->assertSame('c0000000-0000-0000-0000-000000000001', $authDto->companyId);
        $this->assertSame('mock-golog-jwt-token', $token);
        $this->assertSame('c0000000-0000-0000-0000-000000000001', $companyId);
        $this->assertSame('mock-golog-jwt-token', $token2);

        Http::assertSentCount(1);
    }

    public function test_should_get_transport_types(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/type-transport' => Http::response([
                ['id' => '11111111-1111-1111-1111-111111111111', 'name' => 'REFRIGERADO'],
            ], 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $types = $sut->getTransportTypes();

        // Assert
        $this->assertCount(1, $types);
        $this->assertInstanceOf(GoLogTransportTypeResponseDTO::class, $types[0]);
        $this->assertSame('11111111-1111-1111-1111-111111111111', $types[0]->id);
        $this->assertSame('REFRIGERADO', $types[0]->name);
    }

    public function test_should_create_transport_type(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/type-transport' => Http::response([
                'id' => '22222222-2222-2222-2222-222222222222',
                'name' => 'CLIMATIZADO',
                'description' => 'Desc',
                'care' => 'Care',
            ], 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $result = $sut->createTransportType('CLIMATIZADO', 'Desc', 'Care');

        // Assert
        $this->assertInstanceOf(GoLogTransportTypeResponseDTO::class, $result);
        $this->assertSame('22222222-2222-2222-2222-222222222222', $result->id);
        $this->assertSame('CLIMATIZADO', $result->name);
    }

    public function test_should_create_driver_user_driver_tractor_and_equipament_group(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $userFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_user_success.php');
        $driverFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_driver_success.php');
        $tractorFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_tractor_success.php');
        $groupFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_equipament_group_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/user' => Http::response($userFixture, 200),
            'http://golog.test/driver' => Http::response($driverFixture, 200),
            'http://golog.test/tractor' => Http::response($tractorFixture, 200),
            'http://golog.test/equipament-group' => Http::response($groupFixture, 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $userDto = $sut->createUser(
            name: 'Rafael Santos Motorista',
            email: 'rafael@example.com',
            password: 'Password123!',
            cpf: '12345678901',
        );

        $driverDto = $sut->createDriver(
            userId: $userDto->id,
            cnhNumber: '12345678901',
            cnhExpiration: '2028-12-31',
            costPerHour: 45.0,
        );

        $tractorDto = $sut->createTractor(
            plate: 'BRA2E19',
            renavam: '12345678901',
            model: 'Mercedes Sprinter',
            numberAxles: 2,
            maximumCapacity: 1500.5,
            typeFuel: GoLogFuelType::DIESEL,
            costPerKilometer: 2.5,
        );

        $groupDto = $sut->createEquipamentGroup(
            equipament1Id: $tractorDto->id,
            observation: 'Conjunto operacional motorista Cultiva',
        );

        // Assert
        $this->assertInstanceOf(GoLogUserResponseDTO::class, $userDto);
        $this->assertSame('11111111-2222-3333-4444-555555555555', $userDto->id);

        $this->assertInstanceOf(GoLogDriverResponseDTO::class, $driverDto);
        $this->assertSame('22222222-3333-4444-5555-666666666666', $driverDto->id);

        $this->assertInstanceOf(GoLogTractorResponseDTO::class, $tractorDto);
        $this->assertSame('33333333-4444-5555-6666-777777777777', $tractorDto->id);

        $this->assertInstanceOf(GoLogEquipamentGroupResponseDTO::class, $groupDto);
        $this->assertSame('44444444-5555-6666-7777-888888888888', $groupDto->id);
    }
}
