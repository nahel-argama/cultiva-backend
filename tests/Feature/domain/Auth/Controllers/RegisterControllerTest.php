<?php

namespace Tests\Feature\domain\Auth\Controllers;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\Company\Company;
use Cultiva\Models\User\User;
use Database\Factories\UserFactory;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_register_producer_successfully(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);

        $payload = [
            'user' => [
                'name' => 'Maria Silva',
                'email' => 'maria@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'producer',
            'company' => [
                'trade_name' => 'Sítio Primavera',
                'legal_name' => 'Sítio Primavera LTDA',
                'document_number' => '12345678000195',
                'phone' => '00000000000',
                'address' => [
                    'zip' => '89010025',
                    'number' => '123',
                    'complement' => 'Portão Azul',
                    'reference_point' => 'Perto da ponte',
                ],
            ],
            'producer' => [
                'activity_segment' => 'vegetables',
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['name', 'email'],
                    'profile_type',
                    'tokens' => ['access_token', 'refresh_token'],
                    'profile' => ['trade_name', 'activity_segment'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'profile_type' => 'producer',
        ]);

        $user = User::where('email', 'maria@example.com')->first();

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'document_number' => '12345678000195',
            'phone' => '00000000000',
        ]);

        $this->assertDatabaseHas('producers', [
            'company_id'       => $user->company->id,
            'activity_segment' => 'vegetables',
        ]);

        $this->assertDatabaseHas('addresses', [
            'company_id' => $user->company->id,
            'zip' => '89010025',
        ]);
    }

    public function test_should_register_retailer_successfully(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);

        $payload = [
            'user' => [
                'name' => 'Carlos Mendes',
                'email' => 'carlos@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'retailer',
            'company' => [
                'trade_name' => 'Mercado Central',
                'legal_name' => 'Mercado Central LTDA',
                'document_number' => '12345678000195',
                'phone' => '00000000000',
                'address' => [
                    'zip' => '89010025',
                    'number' => '456',
                    'complement' => 'Loja 01',
                    'reference_point' => 'Em frente à praça',
                ],
            ],
            'retailer' => [
                'business_type' => 'supermarket',
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['name', 'email'],
                    'profile_type',
                    'tokens' => ['access_token', 'refresh_token'],
                    'profile' => ['trade_name', 'business_type'],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'carlos@example.com',
            'profile_type' => 'retailer',
        ]);

        $user = User::where('email', 'carlos@example.com')->first();

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'document_number' => '12345678000195',
            'phone' => '00000000000',
        ]);

        $this->assertDatabaseHas('retailers', [
            'company_id' => $user->company->id,
            'business_type' => 'supermarket',
        ]);

        $this->assertDatabaseHas('addresses', [
            'company_id' => $user->company->id,
            'zip' => '89010025',
        ]);
    }

    public function test_should_register_delivery_successfully(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);

        $payload = [
            'user' => [
                'name' => 'Rafael Santos',
                'email' => 'rafael@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'delivery',
            'company' => [
                'trade_name' => 'Santos Log Express',
                'legal_name' => 'Santos Transportes LTDA',
                'document_number' => '12345678000195',
                'phone' => '00000000000',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                    'complement' => 'Galpão B',
                    'reference_point' => 'Próximo à rodovia',
                ],
            ],
            'delivery' => [
                'cnh_number' => '12345678901',
                'cnh_category' => 'B',
                'vehicle' => [
                    'plate' => 'BRA2E19',
                    'cargo_type' => 'refrigerated',
                    'capacity' => 1500.5,
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['name', 'email'],
                    'profile_type',
                    'tokens' => ['access_token', 'refresh_token'],
                    'profile' => [
                        'trade_name',
                        'cnh_category',
                        'vehicle' => ['plate', 'cargo_type', 'capacity'],
                    ],
                ],
            ])
            ->assertJsonPath('data.profile.vehicle.capacity', 1500.5);

        $this->assertDatabaseHas('users', [
            'email' => 'rafael@example.com',
            'profile_type' => 'delivery',
        ]);

        $user = User::where('email', 'rafael@example.com')->first();

        $this->assertDatabaseHas('companies', [
            'user_id' => $user->id,
            'document_number' => '12345678000195',
            'phone' => '00000000000',
        ]);

        $this->assertDatabaseHas('deliveries', [
            'company_id'   => $user->company->id,
            'cnh_number'   => '12345678901',
            'cnh_category' => 'B',
        ]);

        $refrigeratedType = CargoType::where('code', 'refrigerated')->first();

        $this->assertDatabaseHas('vehicles', [
            'delivery_id'   => $user->delivery->id,
            'plate'         => 'BRA2E19',
            'cargo_type_id' => $refrigeratedType->id,
            'capacity'      => 1500.5,
        ]);

        $this->assertDatabaseHas('addresses', [
            'company_id' => $user->company->id,
            'zip' => '89010025',
        ]);
    }

    public function test_should_return_422_when_delivery_vehicle_capacity_is_missing_or_non_positive(): void
    {
        // Arrange
        $payload = [
            'user' => [
                'name' => 'Rafael Santos',
                'email' => 'rafael@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'delivery',
            'company' => [
                'trade_name' => 'Santos Log Express',
                'legal_name' => 'Santos Transportes LTDA',
                'document_number' => '12345678000195',
                'phone' => '00000000000',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                ],
            ],
            'delivery' => [
                'cnh_number' => '12345678901',
                'cnh_category' => 'B',
                'vehicle' => [
                    'plate' => 'BRA2E19',
                    'cargo_type' => 'refrigerated',
                    'capacity' => 0,
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['delivery.vehicle.capacity']);
    }

    public function test_should_return_signup_metadata_successfully(): void
    {
        // Action
        $response = $this->getJson('/v1/auth/signup/metadata');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'profile_type',
                    'activity_segment',
                    'business_type',
                    'cnh_category',
                    'cargo_type',
                ],
            ])
            ->assertJsonPath('data.profile_type.producer', 'Produtor')
            ->assertJsonPath('data.profile_type.retailer', 'Varejista')
            ->assertJsonPath('data.profile_type.delivery', 'Entregador')
            ->assertJsonPath('data.profile_type.golog_driver', 'Motorista GoLog')
            ->assertJsonPath('data.activity_segment.vegetables', 'Hortaliças')
            ->assertJsonPath('data.business_type.supermarket', 'Supermercado')
            ->assertJsonPath('data.cnh_category.A', 'Categoria A')
            ->assertJsonPath('data.cargo_type.dry', 'Carga Seca');
    }

    public function test_should_register_golog_driver_and_vehicle_and_persist_golog_identifiers(): void
    {
        // Arrange
        config([
            'services.golog.base_url' => 'http://golog.test',
            'services.golog.email' => 'admin@golog.test',
            'services.golog.password' => 'secret123',
            'services.golog.timeout' => 5,
        ]);

        $cepFixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $userFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_user_success.php');
        $driverFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_driver_success.php');
        $tractorFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_tractor_success.php');
        $groupFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_equipament_group_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($cepFixture, 200),
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/user' => Http::response($userFixture, 200),
            'http://golog.test/driver' => Http::response($driverFixture, 200),
            'http://golog.test/tractor' => Http::response($tractorFixture, 200),
            'http://golog.test/equipament-group' => Http::response($groupFixture, 200),
        ]);

        $payload = [
            'user' => [
                'name' => 'Rafael Santos Motorista',
                'email' => 'rafael.golog@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'golog_driver',
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
            'golog_driver' => [
                'cpf' => '12345678901',
                'cnh_number' => '12345678901',
                'cnh_expiration' => '2028-12-31',
                'cost_per_hour' => 45.0,
                'vehicle' => [
                    'plate' => 'BRA2E19',
                    'capacity' => 1500.5,
                    'renavam' => '12345678901',
                    'model' => 'Mercedes Sprinter',
                    'number_axles' => 2,
                    'type_fuel' => 'DIESEL',
                    'cost_per_kilometer' => 2.5,
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(201)
            ->assertJsonPath('data.profile_type', 'golog_driver')
            ->assertJsonPath('data.profile.trade_name', 'Santos Log Express')
            ->assertJsonPath('data.profile.golog_user_id', '11111111-2222-3333-4444-555555555555')
            ->assertJsonPath('data.profile.golog_driver_id', '22222222-3333-4444-5555-666666666666')
            ->assertJsonPath('data.profile.vehicle.golog_tractor_id', '33333333-4444-5555-6666-777777777777')
            ->assertJsonPath('data.profile.vehicle.golog_equipament_group_id', '44444444-5555-6666-7777-888888888888');

        $this->assertDatabaseHas('users', [
            'email' => 'rafael.golog@example.com',
            'profile_type' => 'golog_driver',
        ]);

        $user = User::where('email', 'rafael.golog@example.com')->first();

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertDatabaseCount('vehicles', 0);

        $this->assertDatabaseHas('golog_drivers', [
            'company_id' => $user->company->id,
            'golog_user_id' => '11111111-2222-3333-4444-555555555555',
            'golog_driver_id' => '22222222-3333-4444-5555-666666666666',
        ]);

        $this->assertDatabaseHas('golog_vehicles', [
            'golog_driver_id' => $user->gologDriver->id,
            'golog_tractor_id' => '33333333-4444-5555-6666-777777777777',
            'golog_equipament_group_id' => '44444444-5555-6666-7777-888888888888',
        ]);
    }

    public function test_should_rollback_golog_driver_signup_when_golog_api_fails(): void
    {
        // Arrange
        config([
            'services.golog.base_url' => 'http://golog.test',
            'services.golog.email' => 'admin@golog.test',
            'services.golog.password' => 'secret123',
            'services.golog.timeout' => 5,
        ]);

        $cepFixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        $loginFixture = require base_path('tests/Fixtures/Integrations/GoLog/login_success.php');
        $userFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_user_success.php');
        $driverFixture = require base_path('tests/Fixtures/Integrations/GoLog/create_driver_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($cepFixture, 200),
            'http://golog.test/login' => Http::response($loginFixture, 200),
            'http://golog.test/user' => Http::response($userFixture, 200),
            'http://golog.test/driver' => Http::response($driverFixture, 200),
            'http://golog.test/tractor' => Http::response(['message' => 'Internal error'], 500),
        ]);

        $payload = [
            'user' => [
                'name' => 'Rafael Santos Motorista',
                'email' => 'rafael.rollback@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'golog_driver',
            'company' => [
                'trade_name' => 'Santos Log Express',
                'legal_name' => 'Santos Transportes LTDA',
                'document_number' => '12345678000195',
                'phone' => '11987654321',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                ],
            ],
            'golog_driver' => [
                'cpf' => '12345678901',
                'cnh_number' => '12345678901',
                'cnh_expiration' => '2028-12-31',
                'cost_per_hour' => 45.0,
                'vehicle' => [
                    'plate' => 'BRA2E19',
                    'capacity' => 1500.5,
                    'renavam' => '12345678901',
                    'model' => 'Mercedes Sprinter',
                    'number_axles' => 2,
                    'type_fuel' => 'DIESEL',
                    'cost_per_kilometer' => 2.5,
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(500);
        $this->assertDatabaseMissing('users', ['email' => 'rafael.rollback@example.com']);
        $this->assertDatabaseCount('golog_drivers', 0);
        $this->assertDatabaseCount('golog_vehicles', 0);
    }

    public function test_should_return_422_when_golog_driver_required_fields_are_missing(): void
    {
        // Arrange
        $payload = [
            'user' => [
                'name' => 'Rafael Santos Motorista',
                'email' => 'rafael.invalid@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ],
            'profile_type' => 'golog_driver',
            'company' => [
                'trade_name' => 'Santos Log Express',
                'legal_name' => 'Santos Transportes LTDA',
                'document_number' => '12345678000195',
                'phone' => '11987654321',
                'address' => [
                    'zip' => '89010025',
                    'number' => '789',
                ],
            ],
            'golog_driver' => [
                'cnh_number' => '12345678901',
                'vehicle' => [
                    'plate' => 'BRA2E19',
                    'capacity' => 1500.5,
                ],
            ],
        ];

        // Action
        $response = $this->postJson('/v1/auth/signup', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'golog_driver.cpf',
                'golog_driver.cnh_expiration',
                'golog_driver.cost_per_hour',
                'golog_driver.vehicle.renavam',
                'golog_driver.vehicle.model',
                'golog_driver.vehicle.number_axles',
                'golog_driver.vehicle.type_fuel',
                'golog_driver.vehicle.cost_per_kilometer',
            ]);
    }

    public function test_should_upload_document_and_verify_user_when_rg_is_authentic(): void
    {
        // Arrange
        Storage::fake('local');

        $fixture = require base_path('tests/Fixtures/Integrations/DocumentValidation/rg_valid.php');

        Gemini::fake([
            GenerateContentResponse::fake([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $fixture],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        Sanctum::actingAs($user, ['access']);

        $payload = [
            'document' => UploadedFile::fake()->image('rg.jpg'),
        ];

        // Action
        $response = $this->postJson('/v1/auth/upload-document', $payload);

        // Assert
        $response->assertStatus(202)
            ->assertJson([
                'message' => Lang::get('auth.document_verification.queued'),
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_verified' => true,
        ]);

        $this->assertSame([], Storage::disk('local')->allFiles('documents'));
    }

    public function test_should_keep_user_unverified_when_uploaded_document_is_invalid_rg(): void
    {
        // Arrange
        Storage::fake('local');

        $fixture = require base_path('tests/Fixtures/Integrations/DocumentValidation/rg_invalid.php');

        Gemini::fake([
            GenerateContentResponse::fake([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $fixture],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        Sanctum::actingAs($user, ['access']);

        $payload = [
            'document' => UploadedFile::fake()->image('not-an-rg.png'),
        ];

        // Action
        $response = $this->postJson('/v1/auth/upload-document', $payload);

        // Assert
        $response->assertStatus(202)
            ->assertJson([
                'message' => Lang::get('auth.document_verification.queued'),
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_verified' => false,
        ]);

        $this->assertSame([], Storage::disk('local')->allFiles('documents'));
    }

    public function test_should_return_422_when_uploading_document_for_already_verified_user(): void
    {
        // Arrange
        Storage::fake('local');

        $user = UserFactory::new()->create([
            'is_verified' => true,
        ]);

        Sanctum::actingAs($user, ['access']);

        $payload = [
            'document' => UploadedFile::fake()->image('rg.jpg'),
        ];

        // Action
        $response = $this->postJson('/v1/auth/upload-document', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => Lang::get('auth.document_verification.already_verified'),
            ]);
    }

    public function test_should_return_422_when_uploaded_document_has_invalid_mime_type(): void
    {
        // Arrange
        Storage::fake('local');

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        Sanctum::actingAs($user, ['access']);

        $payload = [
            'document' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ];

        // Action
        $response = $this->postJson('/v1/auth/upload-document', $payload);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }

    public function test_should_return_document_validation_status_when_in_cache(): void
    {
        // Arrange
        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        Sanctum::actingAs($user, ['access']);

        Cache::put("document_verification:status:{$user->id}", [
            'status' => 'pending',
            'message' => 'Document verification is in progress.',
        ], now()->addDay());

        // Action
        $response = $this->getJson('/v1/auth/signup/document/status');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'pending',
                    'message' => 'Document verification is in progress.',
                ],
            ]);
    }

    public function test_should_return_404_when_no_document_validation_is_in_cache(): void
    {
        // Arrange
        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        Sanctum::actingAs($user, ['access']);

        Cache::forget("document_verification:status:{$user->id}");

        // Action
        $response = $this->getJson('/v1/auth/signup/document/status');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'message' => Lang::get('auth.document_verification.status_not_found'),
            ]);
    }

    public function test_should_return_401_when_unauthenticated_on_document_status(): void
    {
        // Action
        $response = $this->getJson('/v1/auth/signup/document/status');

        // Assert
        $response->assertStatus(401);
    }
}
