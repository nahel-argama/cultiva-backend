<?php

namespace Tests\Unit\domain\Integrations\Geo\Decorators;

use Closure;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Decorators\CachedGeoProviderDecorator;
use Illuminate\Contracts\Cache\Repository;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CachedGeoProviderDecoratorTest extends TestCase
{
    public function test_should_cache_the_wrapped_provider_result(): void
    {
        // Arrange
        $cep = new Cep('89010-025');
        $expectedAddress = new GeoAddressDTO(
            zipCode: '89010025',
            state: 'SC',
            city: 'Blumenau',
            neighborhood: 'Centro',
            street: 'Rua XV de Novembro',
            latitude: -26.9194,
            longitude: -49.0661,
        );
        $provider = Mockery::mock(GeoProviderContract::class, function (MockInterface $mock) use ($cep, $expectedAddress) {
            $mock->shouldReceive('searchByCep')
                ->once()
                ->with($cep)
                ->andReturn($expectedAddress);
        });
        $cache = Mockery::mock(Repository::class, function (MockInterface $mock) use ($expectedAddress) {
            $mock->shouldReceive('remember')
                ->once()
                ->with('geo:cep:89010025', 3600, Mockery::type(Closure::class))
                ->andReturnUsing(fn (string $key, int $seconds, Closure $callback) => $callback());
        });
        $sut = new CachedGeoProviderDecorator($provider, $cache);

        // Action
        $result = $sut->searchByCep($cep);

        // Assert
        $this->assertSame($expectedAddress, $result);
    }
}
