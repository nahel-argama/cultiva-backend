<?php

namespace Tests\Unit\domain\Auth\Facades;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Facades\SignUpFacade;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SignUpFacadeTest extends TestCase
{
    public function test_should_register_a_profile_through_a_single_interface(): void
    {
        // Arrange
        $dto = SignUpDTO::fromArray([
            'profile_type' => 'producer',
            'user' => [
                'name' => 'Maria Silva',
                'email' => 'facade@example.com',
                'password' => 'Password123!',
            ],
            'company' => [
                'trade_name' => 'Sítio Primavera',
                'legal_name' => 'Sítio Primavera LTDA',
                'document_number' => '12345678000195',
                'phone' => '5511987654321',
                'address' => [
                    'zip' => '89010025',
                    'number' => '123',
                ],
            ],
            'producer' => [
                'activity_segment' => 'vegetables',
            ],
        ]);
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);
        $sut = $this->app->make(SignUpFacade::class);

        // Action
        $result = $sut->register($dto);

        // Assert
        $this->assertInstanceOf(ProfileResultDTO::class, $result);
        $this->assertNotNull($result->tokens);
        $this->assertDatabaseHas('users', ['email' => 'facade@example.com']);
        $this->assertDatabaseHas('producers', ['company_id' => $result->user->company->id]);
    }
}
