<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Clients;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogAuthResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogDriverResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogEquipamentGroupResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportItemDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTractorResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogUserResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
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
            'services.golog.tenant_id' => null,
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

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'http://golog.test/user'
                && $request['userProfile'] === 'DRIVER';
        });

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'http://golog.test/driver'
                && $request['cnhExpiration'] === '31/12/2028';
        });

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'http://golog.test/tractor'
                && $request['typeFuel'] === 'DIESEL';
        });
    }

    public function test_should_import_invoices_in_batch(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $importFixture = require base_path('tests/Fixtures/Integrations/GoLog/import_invoices_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/integration/invoices/json' => Http::response($importFixture, 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        $item = new GoLogInvoiceImportItemDTO(
            invoiceNumber: 'DO-1',
            series: '1',
            accessKey: 'NFE00000001',
            typeOperation: 'ENTREGA',
            weight: 10.0,
            volume: 0.05,
            value: 120.0,
            scheduledDate: '2026-11-04T12:00:00Z',
            notes: '[Frutas] Pedido #1 - Maca',
            senderName: 'Fazenda Santa Maria',
            senderDocument: '12345678000195',
            customerName: 'Hortifruti Bom Preco',
            customerDocument: '98765432000188',
            customerEmail: 'contato@bompreco.com',
            customerPhone: '(11)987654321',
            street: 'Avenida Paulista',
            number: '1000',
            district: 'Bela Vista',
            city: 'Sao Paulo',
            state: 'SP',
            cep: '01310100',
            latitude: '-23.561684',
            longitude: '-46.655981',
        );

        // Action
        $result = $sut->importInvoicesJson([$item]);

        // Assert
        $this->assertInstanceOf(GoLogInvoiceImportResponseDTO::class, $result);
        $this->assertSame(2, $result->totalReceived);
        $this->assertSame(2, $result->successCount);
        $this->assertSame(0, $result->errorCount);
        $this->assertCount(2, $result->createdShipmentIds);
        $this->assertSame('s1111111-1111-1111-1111-111111111111', $result->createdShipmentIds[0]);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://golog.test/integration/invoices/json'
                && $request->method() === 'POST'
                && isset($request->data()['invoices'][0]['invoiceNumber'])
                && $request->data()['invoices'][0]['invoiceNumber'] === 'DO-1';
        });
    }

    public function test_should_create_work_schedule(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $schedulesFixture = require base_path('tests/Fixtures/Integrations/GoLog/list_work_schedules_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/workSchedule' => fn (\Illuminate\Http\Client\Request $request) => $request->method() === 'POST'
                ? Http::response('', 201)
                : Http::response($schedulesFixture, 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $response = $sut->createWorkSchedule(
            driverId: '22222222-3333-4444-5555-666666666666',
            equipamentGroupId: '44444444-5555-6666-7777-888888888888',
            scheduleDate: '2026-11-10',
        );

        // Assert
        $this->assertInstanceOf(\Cultiva\Integrations\GoLog\DTO\GoLogWorkScheduleResponseDTO::class, $response);
        $this->assertSame('55555555-5555-5555-5555-555555555555', $response->id);
        $this->assertSame('2026-11-10', $response->scheduleDate);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://golog.test/workSchedule'
                && $request->method() === 'POST'
                && $request['driverId'] === '22222222-3333-4444-5555-666666666666'
                && $request['equipamentGroupId'] === '44444444-5555-6666-7777-888888888888'
                && $request['scheduleDate'] === '2026-11-10'
                && $request['status'] === 'ATIVO'
                && $request['startWorkday'] === '08:00:00'
                && $request['endWorkday'] === '18:00:00';
        });
    }

    public function test_should_send_tenant_header_and_override_company_id_when_tenant_is_configured(): void
    {
        // Arrange
        config([
            'services.golog.tenant_id' => 't1111111-2222-3333-4444-555555555555',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $userFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_user_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/user' => Http::response($userFixture, 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $companyId = $sut->getCompanyId();
        $sut->createUser(
            name: 'Lucas Ferreira Transporte',
            email: 'lucas@cultiva.com',
            password: 'Password123!',
            cpf: '71428593004',
        );

        // Assert
        $this->assertSame('t1111111-2222-3333-4444-555555555555', $companyId);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'http://golog.test/user'
                && $request->hasHeader('X-Tenant-Id', 't1111111-2222-3333-4444-555555555555')
                && $request['companyId'] === 't1111111-2222-3333-4444-555555555555';
        });
    }

    public function test_should_optimize_routes(): void
    {
        // Arrange
        config([
            'services.golog.tenant_id' => 't1111111-2222-3333-4444-555555555555',
        ]);

        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $optimizeFixture = require base_path('tests/Fixtures/Integrations/GoLog/optimize_routes_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/api-route-optimization' => Http::response($optimizeFixture, 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        $shipmentIds = [
            's1111111-1111-1111-1111-111111111111',
            's2222222-2222-2222-2222-222222222222',
        ];
        $workScheduleIds = [
            'w1111111-1111-1111-1111-111111111111',
        ];

        // Action
        $response = $sut->optimizeRoutes(
            shipmentIds: $shipmentIds,
            workScheduleIds: $workScheduleIds,
            routePriority: GoLogRoutePriority::TEMPO,
        );

        // Assert
        $this->assertSame($optimizeFixture, $response);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($shipmentIds, $workScheduleIds): bool {
            return $request->url() === 'http://golog.test/api-route-optimization'
                && $request->method() === 'POST'
                && $request->hasHeader('X-Tenant-Id', 't1111111-2222-3333-4444-555555555555')
                && $request['shipmentIds'] === $shipmentIds
                && $request['workScheduleIds'] === $workScheduleIds
                && $request['routePriority'] === 'TEMPO';
        });
    }

    public function test_should_reauthenticate_and_retry_once_when_receiving_401(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Cache::put('golog_auth_session', [
            'token' => 'expired-jwt-token',
            'userId' => 'a0000000-0000-0000-0000-000000000001',
            'userName' => 'Cultiva',
            'userRole' => 'ADMIN',
            'companyId' => 'c0000000-0000-0000-0000-000000000001',
            'companyName' => 'Cultiva',
            'companyType' => 'TENANT_HEADQUARTER',
            'isMaster' => false,
        ], 6600);

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/type-transport' => Http::sequence()
                ->push(['message' => 'Sessão ausente ou expirada'], 401)
                ->push([['id' => '11111111-1111-1111-1111-111111111111', 'name' => 'REFRIGERADO']], 200),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action
        $types = $sut->getTransportTypes();

        // Assert
        $this->assertCount(1, $types);
        $this->assertSame('REFRIGERADO', $types[0]->name);
        Http::assertSentCount(3);
        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => $request->url() === 'http://golog.test/type-transport'
            && $request->hasHeader('Authorization', 'Bearer mock-golog-jwt-token'));
    }

    public function test_should_extract_standardized_error_message_when_request_fails(): void
    {
        // Arrange
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/type-transport' => Http::response([
                'status' => 400,
                'message' => 'Placa inválida • RENAVAM deve conter exatamente 11 dígitos numéricos',
                'errors' => [
                    'Placa inválida',
                    'RENAVAM deve conter exatamente 11 dígitos numéricos',
                ],
                'timestamp' => '2026-11-11T03:40:27Z',
            ], 400),
        ]);

        $sut = $this->app->make(GoLogClient::class);

        // Action & Assert
        try {
            $sut->createTransportType('INVALIDO', 'Desc', 'Care');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(400, $exception->getStatusCode());
            $this->assertSame(
                'Failed to create transport type in GoLog: Placa inválida • RENAVAM deve conter exatamente 11 dígitos numéricos',
                $exception->getMessage()
            );
        }
    }
}
