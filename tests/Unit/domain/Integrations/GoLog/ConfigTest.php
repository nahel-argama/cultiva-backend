<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog;

use Cultiva\Integrations\GoLog\Config;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_should_return_configured_base_url_and_trim_trailing_slash(): void
    {
        $configRepo = Mockery::mock(ConfigRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->with('services.golog.base_url')
                ->andReturn('http://golog-host.test:8081/');
        });

        $config = new Config($configRepo);

        $this->assertSame('http://golog-host.test:8081', $config->getBaseUrl());
    }

    public function test_should_return_configured_credentials_and_timeout(): void
    {
        $configRepo = Mockery::mock(ConfigRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->with('services.golog.email')
                ->andReturn('user@test.com');
            $mock->shouldReceive('get')
                ->with('services.golog.password')
                ->andReturn('Secret123!');
            $mock->shouldReceive('get')
                ->with('services.golog.timeout')
                ->andReturn(15);
        });

        $config = new Config($configRepo);

        $this->assertSame('user@test.com', $config->getEmail());
        $this->assertSame('Secret123!', $config->getPassword());
        $this->assertSame(15, $config->getTimeout());
    }

    public function test_should_return_configured_tenant_id_or_null_when_empty(): void
    {
        $configRepo = Mockery::mock(ConfigRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->with('services.golog.tenant_id')
                ->andReturn('t0000000-0000-0000-0000-000000000001', '');
        });

        $sut = new Config($configRepo);

        $this->assertSame('t0000000-0000-0000-0000-000000000001', $sut->getTenantId());
        $this->assertNull($sut->getTenantId());
    }
}
