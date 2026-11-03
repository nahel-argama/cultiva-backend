<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\DTO\GoLogAuthResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogDriverResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogEquipamentGroupResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTractorResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogUserResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogTransportType;
use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleStatus;
use Cultiva\Models\CargoType\Enums\CargoType;
use Tests\TestCase;

class GoLogDTOTest extends TestCase
{
    public function test_should_create_auth_response_dto_from_array(): void
    {
        // Arrange
        $data = [
            'token' => 'jwt-token-123',
            'userId' => 'user-uuid-1',
            'userName' => 'Admin User',
            'userRole' => 'ADMIN',
            'companyId' => 'company-uuid-1',
            'companyName' => 'GoLog Matriz',
            'companyType' => 'TENANT_MASTER',
            'isMaster' => true,
        ];

        // Action
        $sut = GoLogAuthResponseDTO::fromArray($data);

        // Assert
        $this->assertSame('jwt-token-123', $sut->token);
        $this->assertSame('user-uuid-1', $sut->userId);
        $this->assertSame('Admin User', $sut->userName);
        $this->assertSame(GoLogUserProfile::ADMIN, $sut->userRole);
        $this->assertSame('company-uuid-1', $sut->companyId);
        $this->assertTrue($sut->isMaster);
    }

    public function test_should_create_transport_type_response_dto_from_array(): void
    {
        // Arrange
        $data = [
            'id' => 'transport-type-uuid',
            'name' => 'REFRIGERADO',
            'description' => 'Cargas refrigeradas',
            'care' => 'Controle termico',
        ];

        // Action
        $sut = GoLogTransportTypeResponseDTO::fromArray($data);

        // Assert
        $this->assertSame('transport-type-uuid', $sut->id);
        $this->assertSame('REFRIGERADO', $sut->name);
        $this->assertSame('Cargas refrigeradas', $sut->description);
        $this->assertSame('Controle termico', $sut->care);
    }

    public function test_should_create_fleet_response_dtos_from_array(): void
    {
        // Arrange & Action
        $userDto = GoLogUserResponseDTO::fromArray([
            'id' => 'user-uuid',
            'name' => 'Rafael Santos',
            'email' => 'rafael@example.com',
            'cpf' => '12345678901',
            'userProfile' => 'DRIVER',
        ]);

        $driverDto = GoLogDriverResponseDTO::fromArray([
            'id' => 'driver-uuid',
            'cnhNumber' => '12345678901',
            'cnhExpiration' => '2028-12-31',
            'costPerHour' => 45.0,
        ]);

        $tractorDto = GoLogTractorResponseDTO::fromArray([
            'id' => 'tractor-uuid',
            'plate' => 'BRA2E19',
            'renavam' => '12345678901',
            'model' => 'Mercedes Sprinter',
            'numberAxles' => 2,
            'maximumCapacity' => 1500.5,
            'status' => 'ATIVO',
            'typeFuel' => 'DIESEL',
            'costPerKilometer' => 2.5,
            'companyId' => 'company-uuid',
        ]);

        $groupDto = GoLogEquipamentGroupResponseDTO::fromArray([
            'id' => 'group-uuid',
            'observation' => 'Conjunto operacional',
        ]);

        // Assert
        $this->assertSame('user-uuid', $userDto->id);
        $this->assertSame(GoLogUserProfile::DRIVER, $userDto->userProfile);
        $this->assertSame('driver-uuid', $driverDto->id);
        $this->assertSame(45.0, $driverDto->costPerHour);
        $this->assertSame('tractor-uuid', $tractorDto->id);
        $this->assertSame(GoLogFuelType::DIESEL, $tractorDto->typeFuel);
        $this->assertSame(GoLogVehicleStatus::ATIVO, $tractorDto->status);
        $this->assertSame('group-uuid', $groupDto->id);
    }

    public function test_should_resolve_transport_type_enum_from_cargo_type(): void
    {
        // Action & Assert
        $this->assertSame(GoLogTransportType::CARGA_SECA, GoLogTransportType::fromCargoType(CargoType::DRY));
        $this->assertSame(GoLogTransportType::CLIMATIZADO, GoLogTransportType::fromCargoType(CargoType::CLIMATE_CONTROLLED));
        $this->assertSame(GoLogTransportType::REFRIGERADO, GoLogTransportType::fromCargoType(CargoType::REFRIGERATED));
    }
}
