<?php

namespace Tests\Feature\domain\Auth\Controllers;

use Cultiva\Models\Producer\Producer;
use Database\Factories\AddressFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_authenticated_profile(): void
    {
        // Arrange
        $payload = [
            'email' => 'producer@example.com',
            'password' => 'valid-password',
        ];

        $user = UserFactory::new()->create([
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);
        $producer = ProducerFactory::new()->create(['user_id' => $user->id]);
        AddressFactory::new()->create([
            'addressable_type' => Producer::class,
            'addressable_id' => $producer->id,
        ]);

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.profile_type', 'producer')
            ->assertJsonPath('data.producer.trade_name', $producer->trade_name)
            ->assertJsonStructure([
                'data' => [
                    'tokens' => ['access_token', 'refresh_token'],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertNotNull($user->fresh()->last_login);
    }

    public function test_should_return_unauthorized_for_invalid_credentials(): void
    {
        // Arrange
        $payload = [
            'email' => 'missing@example.com',
            'password' => 'invalid-password',
        ];

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertUnauthorized()
            ->assertJson([
                'message' => Lang::get('auth.login.invalid_credentials'),
            ]);
    }
}
