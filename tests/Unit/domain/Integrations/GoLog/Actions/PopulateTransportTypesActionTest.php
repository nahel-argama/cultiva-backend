<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Actions\PopulateTransportTypesAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PopulateTransportTypesActionTest extends TestCase
{
    public function test_should_create_only_missing_transport_types(): void
    {
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock) {
            // Suppose GoLog already has 'REFRIGERADO'
            $mock->shouldReceive('getTransportTypes')
                ->once()
                ->andReturn([
                    new GoLogTransportTypeResponseDTO(id: '11111111-1111-1111-1111-111111111111', name: 'REFRIGERADO'),
                ]);

            // Expect to create only 'CARGA SECA' and 'CLIMATIZADO'
            $mock->shouldReceive('createTransportType')
                ->once()
                ->with('CARGA SECA', Mockery::any(), Mockery::any())
                ->andReturn(new GoLogTransportTypeResponseDTO(id: '22222222-2222-2222-2222-222222222222', name: 'CARGA SECA'));

            $mock->shouldReceive('createTransportType')
                ->once()
                ->with('CLIMATIZADO', Mockery::any(), Mockery::any())
                ->andReturn(new GoLogTransportTypeResponseDTO(id: '33333333-3333-3333-3333-333333333333', name: 'CLIMATIZADO'));
        });

        $sut = new PopulateTransportTypesAction($clientMock);

        $result = $sut->execute();

        $this->assertSame(['REFRIGERADO'], $result['existing']);
        $this->assertSame(['CARGA SECA', 'CLIMATIZADO'], $result['created']);
    }

    public function test_should_not_create_any_transport_type_when_all_are_already_present(): void
    {
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getTransportTypes')
                ->once()
                ->andReturn([
                    new GoLogTransportTypeResponseDTO(id: '1', name: 'CARGA SECA'),
                    new GoLogTransportTypeResponseDTO(id: '2', name: 'CLIMATIZADO'),
                    new GoLogTransportTypeResponseDTO(id: '3', name: 'REFRIGERADO'),
                ]);

            $mock->shouldNotReceive('createTransportType');
        });

        $sut = new PopulateTransportTypesAction($clientMock);

        $result = $sut->execute();

        $this->assertCount(3, $result['existing']);
        $this->assertEmpty($result['created']);
    }
}
