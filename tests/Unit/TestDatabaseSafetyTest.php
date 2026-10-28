<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestDatabaseSafetyTest extends TestCase
{
    public function test_should_use_a_separate_test_database(): void
    {
        // Arrange
        $connection = config('database.default');

        // Action
        $database = config("database.connections.{$connection}.database");

        // Assert
        $this->assertSame('cultiva_test', $database);
    }
}
