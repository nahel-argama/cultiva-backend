<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Override;

abstract class TestCase extends BaseTestCase
{
    use DatabaseMigrations;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }
}
