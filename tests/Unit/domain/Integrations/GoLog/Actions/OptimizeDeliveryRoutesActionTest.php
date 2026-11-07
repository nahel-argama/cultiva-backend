<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Actions\OptimizeDeliveryRoutesAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogRoutePriority;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Database\Factories\DeliveryOrderFactory;
use Database\Factories\GoLogDriverFactory;
use Database\Factories\GoLogVehicleFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OptimizeDeliveryRoutesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_optimize_pending_shipments_with_provisioned_work_schedules_and_update_status(): void
    {
        // Arrange
        $order1 = DeliveryOrderFactory::new()->create();
        $order2 = DeliveryOrderFactory::new()->create();
        $order3 = DeliveryOrderFactory::new()->create();

        $shipment1 = GoLogShipment::query()->create([
            'delivery_order_id' => $order1->id,
            'golog_shipment_id' => '11111111-1111-1111-1111-111111111111',
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);
        $shipment2 = GoLogShipment::query()->create([
            'delivery_order_id' => $order2->id,
            'golog_shipment_id' => '22222222-2222-2222-2222-222222222222',
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);
        GoLogShipment::query()->create([
            'delivery_order_id' => $order3->id,
            'golog_shipment_id' => '33333333-3333-3333-3333-333333333333',
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);

        $driver1 = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a1111111-1111-1111-1111-111111111111',
            'golog_driver_id' => 'b1111111-1111-1111-1111-111111111111',
            'golog_work_schedule_id' => 'c1111111-1111-1111-1111-111111111111',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver1->id,
            'golog_tractor_id' => 'd1111111-1111-1111-1111-111111111111',
            'golog_equipament_group_id' => 'e1111111-1111-1111-1111-111111111111',
        ]);

        $driver2 = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a2222222-2222-2222-2222-222222222222',
            'golog_driver_id' => 'b2222222-2222-2222-2222-222222222222',
            'golog_work_schedule_id' => 'c2222222-2222-2222-2222-222222222222',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $driver2->id,
            'golog_tractor_id' => 'd2222222-2222-2222-2222-222222222222',
            'golog_equipament_group_id' => 'e2222222-2222-2222-2222-222222222222',
        ]);

        $incompleteDriver = GoLogDriverFactory::new()->create([
            'golog_user_id' => 'a3333333-3333-3333-3333-333333333333',
            'golog_driver_id' => 'b3333333-3333-3333-3333-333333333333',
            'golog_work_schedule_id' => 'c3333333-3333-3333-3333-333333333333',
        ]);
        GoLogVehicleFactory::new()->create([
            'golog_driver_id' => $incompleteDriver->id,
            'golog_tractor_id' => 'd3333333-3333-3333-3333-333333333333',
            'golog_equipament_group_id' => null,
        ]);

        // Expects
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('optimizeRoutes')
                ->once()
                ->with(
                    [
                        '11111111-1111-1111-1111-111111111111',
                        '22222222-2222-2222-2222-222222222222',
                    ],
                    [
                        'c1111111-1111-1111-1111-111111111111',
                        'c2222222-2222-2222-2222-222222222222',
                    ],
                    GoLogRoutePriority::TEMPO,
                )
                ->andReturn('"Rotas otimizadas com sucesso"');
        });

        $sut = new OptimizeDeliveryRoutesAction($clientMock);

        // Action
        $result = $sut->execute();

        // Assert
        $this->assertInstanceOf(OptimizeRoutesResultDTO::class, $result);
        $this->assertSame(2, $result->shipmentsOptimized);
        $this->assertSame(2, $result->workSchedulesUsed);
        $this->assertEqualsCanonicalizing(
            [
                '11111111-1111-1111-1111-111111111111',
                '22222222-2222-2222-2222-222222222222',
            ],
            $result->shipmentIds,
        );
        $this->assertEqualsCanonicalizing(
            [
                'c1111111-1111-1111-1111-111111111111',
                'c2222222-2222-2222-2222-222222222222',
            ],
            $result->workScheduleIds,
        );
        $this->assertSame('"Rotas otimizadas com sucesso"', $result->response);

        $this->assertDatabaseHas('golog_shipments', [
            'id' => $shipment1->id,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO->value,
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'id' => $shipment2->id,
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO->value,
        ]);
    }

    public function test_should_return_empty_result_dto_when_no_pending_shipments_exist(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create();
        GoLogShipment::query()->create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's1111111-1111-1111-1111-111111111111',
            'status' => GoLogShipmentStatus::AGUARDANDO_INICIO,
        ]);

        // Expects
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('optimizeRoutes');
        });

        $sut = new OptimizeDeliveryRoutesAction($clientMock);

        // Action
        $result = $sut->execute();

        // Assert
        $this->assertInstanceOf(OptimizeRoutesResultDTO::class, $result);
        $this->assertSame(0, $result->shipmentsOptimized);
        $this->assertSame(0, $result->workSchedulesUsed);
        $this->assertSame([], $result->shipmentIds);
        $this->assertSame([], $result->workScheduleIds);
        $this->assertSame('', $result->response);
    }

    public function test_should_throw_exception_when_pending_shipments_exist_without_provisioned_work_schedules(): void
    {
        // Arrange
        $order = DeliveryOrderFactory::new()->create();
        $shipment = GoLogShipment::query()->create([
            'delivery_order_id' => $order->id,
            'golog_shipment_id' => 's1111111-1111-1111-1111-111111111111',
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        GoLogDriverFactory::new()->create([
            'golog_driver_id' => 'd1111111-1111-1111-1111-111111111111',
            'golog_work_schedule_id' => null,
        ]);

        // Expects
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('optimizeRoutes');
        });

        $sut = new OptimizeDeliveryRoutesAction($clientMock);

        // Action & Assert
        try {
            $sut->execute();
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertDatabaseHas('golog_shipments', [
            'id' => $shipment->id,
            'status' => GoLogShipmentStatus::PENDENTE->value,
        ]);
    }
}
