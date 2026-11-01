<?php

namespace Tests\Unit\domain\Models\Address;

use Cultiva\Models\Address\Address;
use Cultiva\Models\Company\Company;
use Database\Factories\AddressFactory;
use Database\Factories\CompanyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_address_belonging_to_company(): void
    {
        // Arrange & Action
        $address = AddressFactory::new()->create([
            'street' => 'Rua das Palmeiras',
            'number' => '100',
        ]);
        $company = $address->company;

        // Assert
        $this->assertInstanceOf(Address::class, $address);
        $this->assertInstanceOf(Company::class, $company);
        $this->assertSame($address->id, $company->address->id);
        $this->assertNotNull($address->coordinate);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'company_id' => $company->id,
            'street' => 'Rua das Palmeiras',
        ]);
    }

    public function test_should_automatically_create_address_with_coordinate_when_company_is_created(): void
    {
        // Arrange & Action
        $company = CompanyFactory::new()->create();

        // Assert
        $this->assertInstanceOf(Address::class, $company->address);
        $this->assertNotNull($company->address->coordinate);
    }
}
