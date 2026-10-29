<?php

namespace Tests\Unit\domain\Integrations\DocumentValidation;

use Cultiva\Integrations\DocumentValidation\Config as DocumentValidationConfig;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_should_return_configured_gemini_model(): void
    {
        // Arrange & Expects
        $configMock = Mockery::mock(ConfigRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('gemini.model', 'gemini-2.5-flash')
                ->andReturn('gemini-2.5-flash');
        });

        $sut = new DocumentValidationConfig($configMock);

        // Action
        $result = $sut->getModel();

        // Assert
        $this->assertSame('gemini-2.5-flash', $result);
    }
}
