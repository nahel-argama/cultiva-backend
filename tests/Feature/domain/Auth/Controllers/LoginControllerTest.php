<?php

namespace Tests\Feature\domain\Auth\Controllers;

use Cultiva\Models\Company\Company;
use Database\Factories\AddressFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\DeliveryFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\UserFactory;
use Database\Factories\VehicleFactory;
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

        $company = CompanyFactory::new()->create(['user_id' => $user->id]);

        $producer = ProducerFactory::new()->create(['company_id' => $company->id]);

        AddressFactory::new()->create([
            'addressable_type' => Company::class,
            'addressable_id' => $company->id,
        ]);

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

        AddressFactory::new()->create([
            'addressable_type' => Company::class,
            'addressable_id' => $company->id,
        ]);

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

        AddressFactory::new()->create([
            'addressable_type' => Company::class,
            'addressable_id' => $company->id,
        ]);

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
                        'vehicle' => ['plate', 'cargo_type'],
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
}
