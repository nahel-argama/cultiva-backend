<?php

namespace Tests\Feature\domain\Auth\Controllers;

use Cultiva\Models\Company\Company;
use Database\Factories\CompanyFactory;
use Database\Factories\DeliveryFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\UserFactory;
use Database\Factories\VehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\Sanctum;
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

        $company = CompanyFactory::new()->create(['user_id' => $user->id]);

        $producer = ProducerFactory::new()->create(['company_id' => $company->id]);

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.profile_type', 'producer')
            ->assertJsonPath('data.profile.trade_name', $company->trade_name)
            ->assertJsonPath('data.profile.activity_segment', $producer->activity_segment->value)
            ->assertJsonStructure([
                'data' => [
                    'tokens' => ['access_token', 'refresh_token'],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertNotNull($user->fresh()->last_login);

        $this->withToken($response->json('data.tokens.access_token'))
            ->getJson('/v1/offers?search=&page=1&per_page=12')
            ->assertOk();
    }

    public function test_should_return_authenticated_retailer_profile(): void
    {
        // Arrange
        $payload = [
            'email' => 'retailer@example.com',
            'password' => 'valid-password',
        ];

        $user = UserFactory::new()->retailer()->create([
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $company = CompanyFactory::new()->create(['user_id' => $user->id]);

        $retailer = RetailerFactory::new()->create(['company_id' => $company->id]);

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.profile_type', 'retailer')
            ->assertJsonPath('data.profile.trade_name', $company->trade_name)
            ->assertJsonPath('data.profile.business_type', $retailer->business_type->value)
            ->assertJsonStructure([
                'data' => [
                    'tokens' => ['access_token', 'refresh_token'],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 2);
        $this->assertNotNull($user->fresh()->last_login);
    }

    public function test_should_return_authenticated_delivery_profile(): void
    {
        // Arrange
        $payload = [
            'email' => 'delivery@example.com',
            'password' => 'valid-password',
        ];

        $user = UserFactory::new()->delivery()->create([
            'email' => $payload['email'],
            'password' => Hash::make($payload['password']),
        ]);

        $company = CompanyFactory::new()->create(['user_id' => $user->id]);

        $delivery = DeliveryFactory::new()->create(['company_id' => $company->id]);

        VehicleFactory::new()->create(['delivery_id' => $delivery->id]);

        // Action
        $response = $this->postJson('/v1/auth/login', $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.user.email', $payload['email'])
            ->assertJsonPath('data.profile_type', 'delivery')
            ->assertJsonPath('data.profile.trade_name', $company->trade_name)
            ->assertJsonPath('data.profile.cnh_category', $delivery->cnh_category->value)
            ->assertJsonStructure([
                'data' => [
                    'tokens' => ['access_token', 'refresh_token'],
                    'profile' => [
                        'vehicle' => ['plate', 'cargo_type', 'capacity'],
                    ],
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

    public function test_should_return_current_user_profile_and_verification_status_on_me_route(): void
    {
        // Arrange
        $user = UserFactory::new()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'is_verified' => false,
        ]);

        $company = CompanyFactory::new()->create(['user_id' => $user->id]);
        $producer = ProducerFactory::new()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user, ['access']);

        // Action
        $response = $this->getJson('/v1/auth/me');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.user.name', 'Maria Silva')
            ->assertJsonPath('data.user.email', 'maria@example.com')
            ->assertJsonPath('data.user.is_verified', false)
            ->assertJsonPath('data.profile_type', 'producer')
            ->assertJsonPath('data.profile.trade_name', $company->trade_name)
            ->assertJsonPath('data.profile.activity_segment', $producer->activity_segment->value)
            ->assertJsonMissingPath('data.tokens');
    }
}
