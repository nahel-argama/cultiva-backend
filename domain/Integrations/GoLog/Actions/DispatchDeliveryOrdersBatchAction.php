<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\DispatchBatchResultDTO;
use Cultiva\Integrations\GoLog\DTO\GoLogInvoiceImportItemDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
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

    public function execute(): DispatchBatchResultDTO
    {
        /** @var Collection<int, DeliveryOrder> $orders */
        $orders = DeliveryOrder::query()
            ->with([
                'purchase.offer.category',
                'purchase.producer.company.user',
                'purchase.retailer.company.user',
                'pickupAddress' => fn ($query) => $query->selectRaw(
                    'delivery_addresses.*, ST_X(coordinate::geometry) AS longitude, ST_Y(coordinate::geometry) AS latitude'
                ),
                'dropoffAddress' => fn ($query) => $query->selectRaw(
                    'delivery_addresses.*, ST_X(coordinate::geometry) AS longitude, ST_Y(coordinate::geometry) AS latitude'
                ),
            ])
            ->where('status', DeliveryOrderStatus::PENDING)
            ->whereDoesntHave('gologShipments')
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return new DispatchBatchResultDTO(
                totalOrders: 0,
                batchesSent: 0,
                shipmentsCreated: 0,
                byCategory: [],
            );
        }

        $groupedOrders = $orders->groupBy(
            fn (DeliveryOrder $order): string => $order->purchase->offer->category->name->value
        );

        $batchesSent = 0;
        $shipmentsCreated = 0;
        $byCategory = [];

        foreach ($groupedOrders as $category => $groupOrders) {
            $categoryOrders = $groupOrders->values();
            $byCategory[$category] = $categoryOrders->count();

            $invoices = [];
            foreach ($categoryOrders as $order) {
                $invoices[] = $this->buildColetaInvoiceItem($order, $category);
                $invoices[] = $this->buildEntregaInvoiceItem($order, $category);
            }

            $response = $this->goLogClient->importInvoicesJson($invoices);
            $batchesSent++;

            if ($response->errorCount > 0) {
                throw new CultivaException(
                    502,
                    "Failed to import category [{$category}] batch in GoLog: " . implode('; ', $response->errors)
                );
            }

            DB::transaction(function () use ($categoryOrders, $response): void {
                foreach ($categoryOrders as $index => $order) {
                    $coletaShipmentId = $response->createdShipmentIds[$index * 2];
                    $entregaShipmentId = $response->createdShipmentIds[$index * 2 + 1];

                    GoLogShipment::query()->create([
                        'delivery_order_id' => $order->id,
                        'golog_shipment_id' => $coletaShipmentId,
                        'type' => GoLogOperationType::COLETA,
                        'status' => GoLogShipmentStatus::PENDENTE,
                    ]);

                    GoLogShipment::query()->create([
                        'delivery_order_id' => $order->id,
                        'golog_shipment_id' => $entregaShipmentId,
                        'type' => GoLogOperationType::ENTREGA,
                        'status' => GoLogShipmentStatus::PENDENTE,
                    ]);
                }
            });

            $shipmentsCreated += $response->successCount;
        }

        return new DispatchBatchResultDTO(
            totalOrders: $orders->count(),
            batchesSent: $batchesSent,
            shipmentsCreated: $shipmentsCreated,
            byCategory: $byCategory,
        );
    }

    private function buildColetaInvoiceItem(DeliveryOrder $order, string $category): GoLogInvoiceImportItemDTO
    {
        $purchase = $order->purchase;
        $producer = $purchase->producer->company;
        $pickup = $order->pickupAddress;

        return new GoLogInvoiceImportItemDTO(
            invoiceNumber: "DO-{$order->id}-COL",
            series: '1',
            accessKey: 'NFE' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) . 'COL' . date('YmdHis'),
            typeOperation: GoLogOperationType::COLETA->value,
            weight: (float) $order->quantity,
            volume: round($order->quantity * 0.005, 3),
            value: (float) $purchase->total_price,
            scheduledDate: now()->addDay()->utc()->toIso8601ZuluString(),
            notes: "[{$category}] Coleta Pedido #{$purchase->id} - {$purchase->product_name}",
            senderName: $producer->trade_name,
            senderDocument: $producer->document_number,
            customerName: $producer->trade_name,
            customerDocument: $producer->document_number,
            customerEmail: $producer->user?->email ?? '',
            customerPhone: $producer->phone,
            street: $pickup->street,
            number: $pickup->number,
            district: $pickup->neighborhood,
            city: $pickup->city,
            state: $pickup->state,
            cep: $pickup->zip,
            latitude: (string) $pickup->getAttribute('latitude'),
            longitude: (string) $pickup->getAttribute('longitude'),
        );
    }

    private function buildEntregaInvoiceItem(DeliveryOrder $order, string $category): GoLogInvoiceImportItemDTO
    {
        $purchase = $order->purchase;
        $producer = $purchase->producer->company;
        $retailer = $purchase->retailer->company;
        $dropoff = $order->dropoffAddress;

        return new GoLogInvoiceImportItemDTO(
            invoiceNumber: "DO-{$order->id}-ENT",
            series: '1',
            accessKey: 'NFE' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) . 'ENT' . date('YmdHis'),
            typeOperation: GoLogOperationType::ENTREGA->value,
            weight: (float) $order->quantity,
            volume: round($order->quantity * 0.005, 3),
            value: (float) $purchase->total_price,
            scheduledDate: now()->addDay()->utc()->toIso8601ZuluString(),
            notes: "[{$category}] Entrega Pedido #{$purchase->id} - {$purchase->product_name}",
            senderName: $producer->trade_name,
            senderDocument: $producer->document_number,
            customerName: $retailer->trade_name,
            customerDocument: $retailer->document_number,
            customerEmail: $retailer->user?->email ?? '',
            customerPhone: $retailer->phone,
            street: $dropoff->street,
            number: $dropoff->number,
            district: $dropoff->neighborhood,
            city: $dropoff->city,
            state: $dropoff->state,
            cep: $dropoff->zip,
            latitude: (string) $dropoff->getAttribute('latitude'),
            longitude: (string) $dropoff->getAttribute('longitude'),
        );
    }
}
