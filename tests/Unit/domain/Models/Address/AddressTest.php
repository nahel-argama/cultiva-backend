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
        // Arrange
        $company = CompanyFactory::new()->create();

        // Action
        $address = AddressFactory::new()->create([
            'company_id' => $company->id,
            'street' => 'Rua das Palmeiras',
            'number' => '100',
        ]);

        // Assert
        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame($company->id, $address->company->id);
        $this->assertInstanceOf(Company::class, $address->company);
        $this->assertSame($address->id, $company->address->id);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'company_id' => $company->id,
            'street' => 'Rua das Palmeiras',
        ]);
    }
}
