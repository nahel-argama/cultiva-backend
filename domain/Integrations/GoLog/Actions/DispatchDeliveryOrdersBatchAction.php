<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportItemDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryOrder\GoLogShipment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DispatchDeliveryOrdersBatchAction
{
    public function __construct(
        private readonly GoLogClient $goLogClient,
    ) {}

    /**
     * @return array{
     *     total_orders: int,
     *     batches_sent: int,
     *     success_count: int,
     *     error_count: int,
     *     by_category: array<string, int>,
     *     errors: array<int, string>
     * }
     */
    public function execute(): array
    {
        /** @var Collection<int, DeliveryOrder> $orders */
        $orders = DeliveryOrder::query()
            ->with([
                'purchase.offer.category',
                'purchase.producer.company',
                'purchase.retailer.company.user',
                'dropoffAddress',
            ])
            ->where('status', DeliveryOrderStatus::PENDING)
            ->whereDoesntHave('gologShipment')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return [
                'total_orders' => 0,
                'batches_sent' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'by_category' => [],
                'errors' => [],
            ];
        }

        $groupedOrders = $orders->groupBy(fn (DeliveryOrder $order): string => $order->purchase->offer->category->name->value);

        $totalBatches = 0;
        $totalSuccess = 0;
        $totalErrors = 0;
        $byCategorySummary = [];
        $collectedErrors = [];

        foreach ($groupedOrders as $category => $groupOrders) {
            $byCategorySummary[$category] = $groupOrders->count();
            $invoices = [];

            foreach ($groupOrders as $order) {
                $invoices[] = $this->buildInvoiceItem($order, $category);
            }

            $totalBatches++;
            $response = $this->goLogClient->importInvoicesJson($invoices);

            $totalSuccess += $response->successCount;
            $totalErrors += $response->errorCount;

            if (! empty($response->errors)) {
                $collectedErrors = array_merge($collectedErrors, $response->errors);
            }

            DB::transaction(function () use ($groupOrders, $response): void {
                foreach ($groupOrders->values() as $index => $order) {
                    $shipmentId = $response->createdShipmentIds[$index] ?? null;
                    if ($shipmentId !== null) {
                        GoLogShipment::query()->create([
                            'delivery_order_id' => $order->id,
                            'golog_shipment_id' => $shipmentId,
                            'status' => GoLogShipmentStatus::PENDENTE,
                        ]);
                    }
                }
            });
        }

        return [
            'total_orders' => $orders->count(),
            'batches_sent' => $totalBatches,
            'success_count' => $totalSuccess,
            'error_count' => $totalErrors,
            'by_category' => $byCategorySummary,
            'errors' => $collectedErrors,
        ];
    }

    private function buildInvoiceItem(DeliveryOrder $order, string $category): GoLogInvoiceImportItemDTO
    {
        $purchase = $order->purchase;
        $producer = $purchase->producer->company;
        $retailer = $purchase->retailer->company;
        $dropoff = $order->dropoffAddress;

        preg_match('/POINT\s*\(\s*([^\s]+)\s+([^\s]+)\s*\)/i', (string) $dropoff->coordinate, $matches);
        $longitude = $matches[1] ?? '';
        $latitude = $matches[2] ?? '';

        return new GoLogInvoiceImportItemDTO(
            invoiceNumber: "DO-{$order->id}",
            series: '1',
            accessKey: 'NFE' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) . date('YmdHis'),
            typeOperation: 'ENTREGA',
            weight: (float) $order->quantity,
            volume: round($order->quantity * 0.005, 3),
            value: (float) $purchase->total_price,
            scheduledDate: now()->addDay()->toIso8601String(),
            notes: "[{$category}] Pedido #{$purchase->id} - {$purchase->product_name}",
            senderName: $producer->trade_name,
            senderDocument: $producer->document_number,
            customerName: $retailer->trade_name,
            customerDocument: $retailer->document_number,
            customerEmail: $retailer->user->email,
            customerPhone: $retailer->phone,
            street: $dropoff->street,
            number: $dropoff->number,
            district: $dropoff->neighborhood,
            city: $dropoff->city,
            state: $dropoff->state,
            cep: $dropoff->zip,
            latitude: $latitude,
            longitude: $longitude,
        );
    }
}
