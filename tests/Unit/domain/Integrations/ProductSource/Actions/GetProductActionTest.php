<?php

namespace Tests\Unit\domain\Integrations\ProductSource\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Integrations\ProductSource\Clients\ProductSourceClient;
use Cultiva\Integrations\ProductSource\DTO\ProductDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetProductActionTest extends TestCase
{
    public function test_should_return_product_dto_with_exact_textual_id_and_name(): void
    {
        // Arrange
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('status')->andReturn(200);
        $mockResponse->shouldReceive('successful')->andReturn(true);
        $mockResponse->shouldReceive('json')->andReturn([
            'id' => '0002',
            'name' => 'Tomate Italiano',
        ]);

        // Expects
        $clientMock = Mockery::mock(ProductSourceClient::class, function (MockInterface $mock) use ($mockResponse) {
            $mock->shouldReceive('getProduct')
                ->once()
                ->with('0002')
                ->andReturn($mockResponse);
        });

        $sut = new GetProductAction($clientMock);

        // Action
        $result = $sut->execute('0002');

        // Assert
        $this->assertInstanceOf(ProductDTO::class, $result);
        $this->assertSame('0002', $result->id);
        $this->assertSame('Tomate Italiano', $result->name);
    }

    public function test_should_throw_422_when_product_is_not_found(): void
    {
        // Arrange
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('status')->andReturn(404);

        // Expects
        $clientMock = Mockery::mock(ProductSourceClient::class, function (MockInterface $mock) use ($mockResponse) {
            $mock->shouldReceive('getProduct')
                ->once()
                ->with('99')
                ->andReturn($mockResponse);
        });

        $sut = new GetProductAction($clientMock);

        // Action & Assert
        try {
            $sut->execute('99');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_should_throw_503_when_connection_fails(): void
    {
        // Expects
        $clientMock = Mockery::mock(ProductSourceClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getProduct')
                ->once()
                ->with('2')
                ->andThrow(new ConnectionException('Connection timed out'));
        });

        $sut = new GetProductAction($clientMock);

        // Action & Assert
        try {
            $sut->execute('2');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
    }

    public function test_should_throw_503_when_service_returns_server_error(): void
    {
        // Arrange
        $mockResponse = Mockery::mock(Response::class);
        $mockResponse->shouldReceive('status')->andReturn(500);
        $mockResponse->shouldReceive('successful')->andReturn(false);

        // Expects
        $clientMock = Mockery::mock(ProductSourceClient::class, function (MockInterface $mock) use ($mockResponse) {
            $mock->shouldReceive('getProduct')
                ->once()
                ->with('2')
                ->andReturn($mockResponse);
        });

        $sut = new GetProductAction($clientMock);

        // Action & Assert
        try {
            $sut->execute('2');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
    }
}
