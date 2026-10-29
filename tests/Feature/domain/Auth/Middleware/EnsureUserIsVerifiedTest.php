<?php

namespace Tests\Feature\domain\Auth\Middleware;

use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class EnsureUserIsVerifiedTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_allow_route_for_verified_user(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $retailer->company->user->update([
            'is_verified' => true,
        ]);

        Sanctum::actingAs($retailer->company->user->fresh(), ['access']);

        // Action & Assert
        $this->getJson('/v1/purchases')->assertOk();
    }

    public function test_should_forbid_route_for_unverified_user(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $producer->company->user->update([
            'is_verified' => false,
        ]);

        Sanctum::actingAs($producer->company->user->fresh(), ['access']);

        // Action & Assert
        $this->getJson('/v1/offers')
            ->assertForbidden()
            ->assertExactJson([
                'message' => __('auth.unverified'),
                'context' => [],
            ]);
    }
}
