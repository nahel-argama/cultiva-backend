<?php

declare(strict_types=1);

namespace Tests\Feature\domain\Integrations\GoLog\Http\Controllers\GoLogDriverController;

use Cultiva\Models\Delivery\Enums\GoLogDriverStep;
use Cultiva\Models\User\User;
use Database\Factories\GoLogDriverFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class StoreUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.golog.base_url' => 'http://golog.test',
            'services.golog.email' => 'admin@golog.test',
            'services.golog.password' => 'secret123',
            'services.golog.timeout' => 5,
        ]);
    }

    public function test_should_create_golog_user_first_then_persist_cultiva_entities_and_return_201_with_tokens(): void
    {
        // Arrange
        $cepFixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $userFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_user_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($cepFixture, 200),
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/user' => Http::response($userFixture, 200),
        ]);

        $payload = [
            'user' => [
                'name' => 'Rafael Santos Motorista',
                'email' => 'rafael.golog@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'cpf' => '40374708029',
            ],
            'company' => [
                'trade_name' => 'Santos Log Express',
                'legal_name' => 'Santos Transportes LTDA',
                'document_number' => '12345678000195',
                'phone' => '11987654321',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                    'complement' => 'Galpão B',
                    'reference_point' => 'Próximo à rodovia',
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/golog/users', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.user.email', 'rafael.golog@example.com')
            ->assertJsonPath('data.profile_type', 'golog_driver')
            ->assertJsonPath('data.profile.trade_name', 'Santos Log Express')
            ->assertJsonPath('data.profile.cpf', '40374708029')
            ->assertJsonPath('data.profile.golog_user_id', '11111111-2222-3333-4444-555555555555')
            ->assertJsonPath('data.profile.golog_driver_id', null)
            ->assertJsonPath('data.profile.stopped_step', 'driver')
            ->assertJsonPath('data.profile.is_provisioned', false)
            ->assertJsonStructure([
                'data' => [
                    'tokens' => [
                        'access_token',
                        'refresh_token',
                    ],
                ],
            ]);

        Http::assertNotSent(fn ($request): bool => $request->url() === 'http://golog.test/driver');

        $user = User::query()->where('email', 'rafael.golog@example.com')->firstOrFail();

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'document_number' => '12345678000195',
        ]);

        $this->assertDatabaseHas('addresses', [
            'company_id' => $user->company->id,
            'zip' => '89010025',
        ]);

        $this->assertDatabaseHas('golog_drivers', [
            'company_id' => $user->company->id,
            'cpf' => '40374708029',
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => null,
        ]);

        $this->assertSame(GoLogDriverStep::DRIVER, $user->gologDriver->getStoppedStep());
    }

    public function test_should_not_persist_cultiva_entities_when_golog_user_creation_fails(): void
    {
        // Arrange
        $cepFixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($cepFixture, 200),
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/user' => Http::response(['message' => 'invalid CPF'], 400),
        ]);

        $payload = [
            'user' => [
                'name' => 'Rafael Santos Motorista',
                'email' => 'rafael.fail@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'cpf' => '40374708029',
            ],
            'company' => [
                'trade_name' => 'Santos Log Express',
                'document_number' => '12345678000195',
                'phone' => '11987654321',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/golog/users', $payload);

        // Assert
        $response->assertStatus(400);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('addresses', 0);
        $this->assertDatabaseCount('golog_drivers', 0);
    }

    public function test_should_return_409_when_email_or_cpf_already_exists(): void
    {
        // Arrange
        Http::fake();

        $existingDriver = GoLogDriverFactory::new()->create([
            'cpf' => '40374708029',
        ]);

        $payload = [
            'user' => [
                'name' => 'Rafael Santos Motorista',
                'email' => $existingDriver->company->user->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'cpf' => '40374708029',
            ],
            'company' => [
                'trade_name' => 'Santos Log Express',
                'document_number' => '98765432000199',
                'phone' => '11987654321',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/golog/users', $payload);

        // Assert
        $response->assertStatus(409);
        Http::assertNothingSent();
    }
}
