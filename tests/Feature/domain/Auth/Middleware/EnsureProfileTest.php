<?php

namespace Tests\Feature\domain\Auth\Middleware;

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
}
