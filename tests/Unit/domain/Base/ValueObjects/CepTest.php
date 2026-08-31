<?php

namespace Tests\Unit\domain\Base\ValueObjects;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Base\ValueObjects\Cep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CepTest extends TestCase
{
    public function test_should_instantiate_cep_with_formatted_string(): void
    {
        // Arrange
        $raw = '89010-025';

        // Action
        $sut = new Cep($raw);

        // Assert
        $this->assertSame('89010025', $sut->value());
        $this->assertSame('89010-025', $sut->format());
    }

    public function test_should_instantiate_cep_with_unformatted_string(): void
    {
        // Arrange
        $raw = '89010025';

        // Action
        $sut = new Cep($raw);

        // Assert
        $this->assertSame('89010025', $sut->value());
        $this->assertSame('89010-025', $sut->format());
    }

    #[DataProvider('invalidCepDataProvider')]
    public function test_should_throw_cultiva_exception_for_invalid_cep(string $invalidCep): void
    {
        // Arrange
        $raw = $invalidCep;

        // Action & Assert
        $this->expectException(CultivaException::class);
        $this->expectExceptionMessage('CEP must contain exactly 8 digits.');
        new Cep($raw);
    }

    public static function invalidCepDataProvider(): array
    {
        return [
            'too short' => ['1234567'],
            'too long' => ['123456789'],
            'non-digit characters' => ['abcdefgh'],
            'empty string' => [''],
            'symbols only' => ['---..'],
        ];
    }
}
