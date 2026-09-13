<?php

namespace Tests\Feature\domain\Auth\Middleware;

use Database\Factories\DeliveryFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EnsureProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_allow_route_for_matching_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action & Assert
        $this->getJson('/v1/purchases')->assertOk();
    }

    public function test_should_forbid_route_for_non_matching_profile(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action & Assert
        $this->getJson('/v1/purchases')
            ->assertForbidden()
            ->assertExactJson([
                'message' => __('auth.forbidden'),
                'context' => [],
            ]);
    }

    public function test_should_allow_route_for_any_matching_profile_when_multiple_profiles_are_configured(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $retailer = RetailerFactory::new()->create();

        // Action & Assert
        Sanctum::actingAs($producer->company->user, ['access']);
        $this->getJson('/v1/offers')->assertOk();

        Sanctum::actingAs($retailer->company->user, ['access']);
        $this->getJson('/v1/offers')->assertOk();
    }

    public function test_should_forbid_route_when_profile_matches_none_of_multiple_configured_profiles(): void
    {
        // Arrange
        $delivery = DeliveryFactory::new()->create();
        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action & Assert
        $this->getJson('/v1/offers')
            ->assertForbidden()
            ->assertExactJson([
                'message' => __('auth.forbidden'),
                'context' => [],
            ]);
    }
}
