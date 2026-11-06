<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Category\Enums\Category as CategoryEnum;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
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

    public function test_should_dispatch_delivery_orders_aggregated_by_category_and_save_golog_shipments(): void
    {
        // Arrange
        $categoryFrutas = Category::query()->where('name', CategoryEnum::FRUTAS)->firstOrFail();
        $categoryVerduras = Category::query()->where('name', CategoryEnum::VERDURAS)->firstOrFail();

        $offerFrutas1 = OfferFactory::new()->create(['category_id' => $categoryFrutas->id]);
        $offerFrutas2 = OfferFactory::new()->create(['category_id' => $categoryFrutas->id]);
        $offerVerduras = OfferFactory::new()->create(['category_id' => $categoryVerduras->id]);

        $purchase1 = PurchaseFactory::new()->create(['offer_id' => $offerFrutas1->id]);
        $purchase2 = PurchaseFactory::new()->create(['offer_id' => $offerFrutas2->id]);
        $purchase3 = PurchaseFactory::new()->create(['offer_id' => $offerVerduras->id]);

        $order1 = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase1->id,
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

        // Order already dispatched (should not be sent again)
        $alreadyDispatched = DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase1->id,
            'status' => DeliveryOrderStatus::PENDING,
        ]);
        GoLogShipment::query()->create([
            'delivery_order_id' => $alreadyDispatched->id,
            'golog_shipment_id' => 's0000000-0000-0000-0000-000000000000',
            'status' => GoLogShipmentStatus::PENDENTE,
        ]);

        // Non-pending order (should not be sent)
        DeliveryOrderFactory::new()->create([
            'purchase_id' => $purchase1->id,
            'status' => DeliveryOrderStatus::IN_TRANSIT,
        ]);

        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock) {
            // Frutas batch (2 orders)
            $mock->shouldReceive('importInvoicesJson')
                ->once()
                ->with(Mockery::on(function (array $invoices): bool {
                    return count($invoices) === 2
                        && str_starts_with($invoices[0]->invoiceNumber, 'DO-')
                        && str_contains($invoices[0]->notes, '[Frutas]');
                }))
                ->andReturn(new GoLogInvoiceImportResponseDTO(
                    totalReceived: 2,
                    successCount: 2,
                    errorCount: 0,
                    createdShipmentIds: [
                        's1111111-1111-1111-1111-111111111111',
                        's2222222-2222-2222-2222-222222222222',
                    ],
                    errors: [],
                ));

            // Verduras batch (1 order)
            $mock->shouldReceive('importInvoicesJson')
                ->once()
                ->with(Mockery::on(function (array $invoices): bool {
                    return count($invoices) === 1
                        && str_contains($invoices[0]->notes, '[Verduras]');
                }))
                ->andReturn(new GoLogInvoiceImportResponseDTO(
                    totalReceived: 1,
                    successCount: 1,
                    errorCount: 0,
                    createdShipmentIds: [
                        's3333333-3333-3333-3333-333333333333',
                    ],
                    errors: [],
                ));
        });

        $sut = new DispatchDeliveryOrdersBatchAction($clientMock);

        // Action
        $result = $sut->execute();

        // Assert
        $this->assertSame(3, $result['total_orders']);
        $this->assertSame(2, $result['batches_sent']);
        $this->assertSame(3, $result['success_count']);
        $this->assertSame(0, $result['error_count']);
        $this->assertSame(2, $result['by_category']['Frutas']);
        $this->assertSame(1, $result['by_category']['Verduras']);

        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order1->id,
            'golog_shipment_id' => 's1111111-1111-1111-1111-111111111111',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order2->id,
            'golog_shipment_id' => 's2222222-2222-2222-2222-222222222222',
            'status' => 'PENDENTE',
        ]);
        $this->assertDatabaseHas('golog_shipments', [
            'delivery_order_id' => $order3->id,
            'golog_shipment_id' => 's3333333-3333-3333-3333-333333333333',
            'status' => 'PENDENTE',
        ]);
    }

    public function test_should_return_empty_summary_when_no_pending_orders_exist(): void
    {
        $clientMock = Mockery::mock(GoLogClient::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('importInvoicesJson');
        });

        $sut = new DispatchDeliveryOrdersBatchAction($clientMock);

        $result = $sut->execute();

        $this->assertSame(0, $result['total_orders']);
        $this->assertSame(0, $result['batches_sent']);
        $this->assertSame(0, $result['success_count']);
        $this->assertSame([], $result['by_category']);
    }
}
