<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;
use Override;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    #[Override]
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');

        if ($app['config']->get("database.connections.{$connection}.database") !== 'cultiva_test') {
            throw new LogicException('Tests must use the isolated cultiva_test database.');
        }

        return $app;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }
}
