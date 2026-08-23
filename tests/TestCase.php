<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Override;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }
}
