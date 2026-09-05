<?php

namespace Tests\Feature\bootstrap;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AppTest extends TestCase
{
    public function test_should_return_clear_message_when_request_is_unauthenticated(): void
    {
        // Arrange
        Route::get('/_test/unauthenticated', fn () => throw new AuthenticationException);

        // Action
        $response = $this->getJson('/_test/unauthenticated');

        // Assert
        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_should_return_clear_message_when_authorization_is_denied(): void
    {
        // Arrange
        Route::get('/_test/authorization-denied', fn () => throw new AuthorizationException);

        // Action
        $response = $this->getJson('/_test/authorization-denied');

        // Assert
        $response->assertForbidden()
            ->assertJsonPath('message', 'You do not have permission to access this resource.');
    }

    public function test_should_return_clear_message_when_profile_middleware_denies_access(): void
    {
        // Arrange
        Route::get('/_test/profile-denied', fn () => abort(403));

        // Action
        $response = $this->getJson('/_test/profile-denied');

        // Assert
        $response->assertForbidden()
            ->assertJsonPath('message', 'You do not have permission to access this resource.');
    }
}
