<?php

namespace Tests\Unit\domain\Models\DeliveryOrder;

use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Database\Factories\DeliveryAddressFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_delivery_address(): void
    {
        // Arrange & Action
        $address = DeliveryAddressFactory::new()->create([
            'zip' => '89010025',
            'street' => 'Rua das Flores',
            'number' => '123',
            'complement' => 'Apto 1',
            'neighborhood' => 'Centro',
            'city' => 'Blumenau',
            'state' => 'SC',
        ]);

        // Assert
        $this->assertInstanceOf(DeliveryAddress::class, $address);
        $this->assertSame('89010025', $address->zip);
        $this->assertSame('Rua das Flores', $address->street);
        $this->assertSame('123', $address->number);
        $this->assertSame('Apto 1', $address->complement);
        $this->assertSame('Centro', $address->neighborhood);
        $this->assertSame('Blumenau', $address->city);
        $this->assertSame('SC', $address->state);
        $this->assertDatabaseHas('delivery_addresses', [
            'id' => $address->id,
            'zip' => '89010025',
            'street' => 'Rua das Flores',
        ]);
    }
}
