<?php

namespace Tests\Unit\domain\Models\Address\Action;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Address\Action\CreateAddressAction;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Address\DTOs\AddressRegisterDTO;
use Cultiva\Models\Company\Company;
use Database\Factories\CompanyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAddressActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_address_for_company(): void
    {
        // Arrange
        $company = CompanyFactory::new()->create();

        $dto = AddressRegisterDTO::fromArray([
            'zip' => '89010025',
            'number' => '123',
            'complement' => 'Apto 101',
            'reference_point' => 'Perto da praça',
        ]);

        $geo = new GeoAddressDTO(
            zipCode: '89010025',
            state: 'SC',
            city: 'Blumenau',
            neighborhood: 'Centro',
            street: 'Rua das Flores',
            latitude: -26.9244749,
            longitude: -49.0629788,
        );

        $sut = new CreateAddressAction();

        // Action
        $result = $sut->execute($company, $dto, $geo);

        // Assert
        $this->assertInstanceOf(Address::class, $result);
        $this->assertSame($company->id, $result->company_id);
        $this->assertSame('89010025', $result->zip);
        $this->assertSame('Rua das Flores', $result->street);
        $this->assertSame('123', $result->number);
        $this->assertSame('Apto 101', $result->complement);
        $this->assertSame('Perto da praça', $result->reference_point);
        $this->assertSame('Centro', $result->neighborhood);
        $this->assertSame('Blumenau', $result->city);
        $this->assertSame('SC', $result->state);

        $this->assertDatabaseHas('addresses', [
            'id' => $result->id,
            'company_id' => $company->id,
            'zip' => '89010025',
            'number' => '123',
        ]);
    }
}
