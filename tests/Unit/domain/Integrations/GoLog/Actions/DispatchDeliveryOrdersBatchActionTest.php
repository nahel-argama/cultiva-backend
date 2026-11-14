<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Cultiva\Integrations\GoLog\Actions\ResolveTransportTypeAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\DispatchBatchResultDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Category\Enums\Category as CategoryEnum;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Database\Factories\DeliveryAddressFactory;
use Database\Factories\DeliveryOrderFactory;
use Database\Factories\OfferFactory;
use Database\Factories\PurchaseFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DispatchDeliveryOrdersBatchActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_dispatch_one_batch_per_category_extract_postgis_coordinates_and_save_shipments(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-13 20:00:00', 'America/Sao_Paulo'));

        $categoryFrutas = Category::query()->where('name', CategoryEnum::FRUTAS)->firstOrFail();
        $categoryVerduras = Category::query()->where('name', CategoryEnum::VERDURAS)->firstOrFail();

        $offerFrutas1 = OfferFactory::new()->create(['category_id' => $categoryFrutas->id]);
        $offerFrutas2 = OfferFactory::new()->create(['category_id' => $categoryFrutas->id]);
        $offerVerduras = OfferFactory::new()->create(['category_id' => $categoryVerduras->id]);

        $purchase1 = PurchaseFactory::new()->create(['offer_id' => $offerFrutas1->id]);
        $purchase2 = PurchaseFactory::new()->create(['offer_id' => $offerFrutas2->id]);
        $purchase3 = PurchaseFactory::new()->create(['offer_id' => $offerVerduras->id]);

        $pickupAddress = DeliveryAddressFactory::new()->create([
            'coordinate' => 'POINT(-49.05555 -26.92222)',
        ]);
        $dropoffAddress = DeliveryAddressFactory::new()->create([
            'coordinate' => 'POINT(-49.06611 -26.91944)',
        ]);

        $order1 = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase1->id,
            'pickup_address_id' => $pickupAddress->id,
            'dropoff_address_id' => $dropoffAddress->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
        $order2 = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase2->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
        $order3 = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase3->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);

        $alreadyDispatched = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase1->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
        GoLogShipment::query()->create([
            'delivery_order_id' => $alreadyDispatched->id,
            'golog_shipment_id' => 's0000000-0000-0000-0000-000000000000',
            'type' => GoLogOperationType::ENTREGA,
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase1->id,
            'status' => DeliveryOrderStatus::IN_TRANSIT,
        ]);

        // Expects
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock) use ($order1): void {
            $mock->shouldReceive('importInvoicesJson')
                ->once()
                ->with(Mockery::on(function (array $invoices) use ($order1): bool {
                    return count($invoices) === 4
                        && $invoices[0]->invoiceNumber === "DO-{$order1->id}-COL"
                        && $invoices[0]->typeOperation === 'COLETA'
                        && $invoices[0]->typeTransportName === 'REFRIGERADO'
                        && $invoices[0]->shipmentTypeName === 'Frutas'
                        && $invoices[0]->scheduledDate === '2026-11-16T15:00:00Z'
                        && str_contains($invoices[0]->notes, '[Frutas]')
                        && $invoices[0]->longitude === '-49.05555'
                        && $invoices[0]->latitude === '-26.92222'
                        && $invoices[1]->invoiceNumber === "DO-{$order1->id}-ENT"
                        && $invoices[1]->typeOperation === 'ENTREGA'
                        && $invoices[1]->typeTransportName === 'REFRIGERADO'
                        && $invoices[1]->shipmentTypeName === 'Frutas'
                        && $invoices[1]->scheduledDate === '2026-11-16T18:00:00Z'
                        && $invoices[1]->longitude === '-49.06611'
                        && $invoices[1]->latitude === '-26.91944';
                }))
                ->andReturn(new GoLogInvoiceImportResponseDTO(
                    totalReceived: 4,
                    successCount: 4,
                    errorCount: 0,
                    createdShipmentIds: [
                        's1111111-col-1111-1111-111111111111',
                        's1111111-ent-1111-1111-111111111111',
                        's2222222-col-2222-2222-222222222222',
                        's2222222-ent-2222-2222-222222222222',
                    ],
                    errors: [],
                ));

            $mock->shouldReceive('importInvoicesJson')
                ->once()
                ->with(Mockery::on(function (array $invoices): bool {
                    return count($invoices) === 2
                        && $invoices[0]->typeOperation === 'COLETA'
                        && $invoices[0]->typeTransportName === 'REFRIGERADO'
                        && $invoices[0]->shipmentTypeName === 'Verduras'
                        && $invoices[1]->typeOperation === 'ENTREGA'
                        && str_contains($invoices[0]->notes, '[Verduras]');
                }))
                ->andReturn(new GoLogInvoiceImportResponseDTO(
                    totalReceived: 2,
                    successCount: 2,
                    errorCount: 0,
                    createdShipmentIds: [
                        's3333333-col-3333-3333-333333333333',
                        's3333333-ent-3333-3333-333333333333',
                    ],
                    errors: [],
                ));
        });

        $sut = new DispatchDeliveryOrdersBatchAction($clientMock, new ResolveTransportTypeAction());

        // Action
        $result = $sut->execute();

        CarbonImmutable::setTestNow();

        // Assert
        $this->assertInstanceOf(DispatchBatchResultDTO::class, $result);
        $this->assertSame(3, $result->totalOrders);
        $this->assertSame(2, $result->batchesSent);
        $this->assertSame(6, $result->shipmentsCreated);
        $this->assertEqualsCanonicalizing(
            ['Frutas' => 2, 'Verduras' => 1],
            $result->byCategory,
        );

        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order1->id,
            'golog_shipment_id' => 's1111111-col-1111-1111-111111111111',
            'type' => 'COLETA',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order1->id,
            'golog_shipment_id' => 's1111111-ent-1111-1111-111111111111',
            'type' => 'ENTREGA',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order2->id,
            'golog_shipment_id' => 's2222222-col-2222-2222-222222222222',
            'type' => 'COLETA',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order2->id,
            'golog_shipment_id' => 's2222222-ent-2222-2222-222222222222',
            'type' => 'ENTREGA',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order3->id,
            'golog_shipment_id' => 's3333333-col-3333-3333-333333333333',
            'type' => 'COLETA',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order3->id,
            'golog_shipment_id' => 's3333333-ent-3333-3333-333333333333',
            'type' => 'ENTREGA',
            'status' => 'PENDENTE',
        ]);
    }

    public function test_should_persist_succeeded_category_batch_and_fail_fast_when_subsequent_batch_returns_errors(): void
    {
        // Arrange
        $categoryFrutas = Category::query()->where('name', CategoryEnum::FRUTAS)->firstOrFail();
        $categoryVerduras = Category::query()->where('name', CategoryEnum::VERDURAS)->firstOrFail();

        $offerFrutas = OfferFactory::new()->create(['category_id' => $categoryFrutas->id]);
        $offerVerduras = OfferFactory::new()->create(['category_id' => $categoryVerduras->id]);

        $purchaseFrutas = PurchaseFactory::new()->create(['offer_id' => $offerFrutas->id]);
        $purchaseVerduras = PurchaseFactory::new()->create(['offer_id' => $offerVerduras->id]);

        $orderFrutas = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchaseFrutas->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
        $orderVerduras = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchaseVerduras->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);

        // Expects
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldReceive('importInvoicesJson')
                ->once()
                ->andReturn(new GoLogInvoiceImportResponseDTO(
                    totalReceived: 2,
                    successCount: 2,
                    errorCount: 0,
                    createdShipmentIds: [
                        's1111111-col-1111-1111-111111111111',
                        's1111111-ent-1111-1111-111111111111',
                    ],
                    errors: [],
                ));

            $mock->shouldReceive('importInvoicesJson')
                ->once()
                ->andReturn(new GoLogInvoiceImportResponseDTO(
                    totalReceived: 2,
                    successCount: 0,
                    errorCount: 1,
                    createdShipmentIds: [],
                    errors: ['Invalid customer document'],
                ));
        });

        $sut = new DispatchDeliveryOrdersBatchAction($clientMock, new ResolveTransportTypeAction());

        // Action & Assert
        try {
            $sut->execute();
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(502, $exception->getStatusCode());
            $this->assertStringContainsString('Invalid customer document', $exception->getMessage());
        }

        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $orderFrutas->id,
            'golog_shipment_id' => 's1111111-col-1111-1111-111111111111',
            'type' => 'COLETA',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $orderFrutas->id,
            'golog_shipment_id' => 's1111111-ent-1111-1111-111111111111',
            'type' => 'ENTREGA',
        ]);
        $this->assertDatabaseMissing('golog_shipments', [
            'delivery_order_id' => $orderVerduras->id,
        ]);
    }

    public function test_should_return_empty_result_dto_when_no_pending_orders_exist(): void
    {
        // Expects
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('importInvoicesJson');
        });

        $sut = new DispatchDeliveryOrdersBatchAction($clientMock, new ResolveTransportTypeAction());

        // Action
        $result = $sut->execute();

        // Assert
        $this->assertInstanceOf(DispatchBatchResultDTO::class, $result);
        $this->assertSame(0, $result->totalOrders);
        $this->assertSame(0, $result->batchesSent);
        $this->assertSame(0, $result->shipmentsCreated);
        $this->assertSame([], $result->byCategory);
    }
}
