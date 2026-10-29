<?php

namespace Tests\Unit\domain\Models\User\Transformers;

use Cultiva\Models\User\Transformers\UserTransformer;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTransformerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_transform_user_with_is_verified_flag(): void
    {
        // Arrange
        $user = UserFactory::new()->create([
            'name' => 'Ana Costa',
            'email' => 'ana@example.com',
            'is_verified' => false,
        ]);

        $sut = new UserTransformer;

        // Action
        $result = $sut->transform($user->fresh());

        // Assert
        $this->assertEqualsCanonicalizing([
            'name' => 'Ana Costa',
            'email' => 'ana@example.com',
            'is_verified' => false,
        ], $result);
    }
}
