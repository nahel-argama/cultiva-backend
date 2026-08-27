<?php

namespace Tests\Feature\domain\Auth\Controllers;

use Cultiva\Auth\Actions\LoginAction;
use Cultiva\Auth\DTO\AuthTokensDTO;
use Cultiva\Auth\DTO\LoginDTO;
use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\User\User;
use DomainException;
use Illuminate\Support\Facades\Lang;
use Mockery;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    public function test_should_return_authenticated_profile(): void
    {
        // Arrange
        $payload = [
            'email' => 'producer@example.com',
            'password' => 'valid-password',
        ];

        $user = new User(['name' => 'Producer', 'email' => $payload['email']]);
        $producer = new Producer([
            'trade_name' => 'Sítio Teste',
            'legal_name' => 'Produtor Teste',
            'is_company' => false,
            'document_number' => '12345678901',
            'phone' => '11999999999',
        ]);
        $producer->setRelation('address', new Address([
            'zip' => '12345678',
            'street' => 'Rua Teste',
            'number' => '1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]));

        $result = new ProfileResultDTO(
            user: $user,
            profileType: ProfileType::PRODUCER,
            producer: $producer,
            retailer: null,
            tokens: new AuthTokensDTO('access-token', 'refresh-token'),
        );

        $action = Mockery::mock(LoginAction::class);
        $action->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(
                fn (LoginDTO $dto) => $dto->email === $payload['email'] && $dto->password === $payload['password'],
            ))
            ->andReturn($result);
        $this->app->instance(LoginAction::class, $action);

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.profile_type', 'producer')
            ->assertJsonPath('data.tokens.access_token', 'access-token');
    }

    public function test_should_return_domain_exception_http_status(): void
    {
        // Arrange
        $payload = [
            'email' => 'missing@example.com',
            'password' => 'invalid-password',
        ];

        $action = Mockery::mock(LoginAction::class);
        $action->shouldReceive('execute')
            ->once()
            ->andThrow(new DomainException(Lang::get('auth.login.invalid_credentials'), 401));
        $this->app->instance(LoginAction::class, $action);

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertUnauthorized()
            ->assertJson([
                'message' => Lang::get('auth.login.invalid_credentials'),
            ]);
    }
}
