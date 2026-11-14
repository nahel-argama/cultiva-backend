<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Carbon\CarbonImmutable;
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
    private const string SCHEDULE_TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        private readonly GoLogClient $goLogClient,
        private readonly ResolveTransportTypeAction $resolveTransportType,
    ) {}

    public function execute(?string $targetDate = null): DispatchBatchResultDTO
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

        $scheduleDate = $targetDate !== null
            ? CarbonImmutable::createFromFormat('Y-m-d', $targetDate, self::SCHEDULE_TIMEZONE)
            : CarbonImmutable::now(self::SCHEDULE_TIMEZONE)->nextWeekday();

        $groupedOrders = $orders->groupBy(
            fn (DeliveryOrder $order): string => $order->purchase->offer->category->name->value
        );

        $batchesSent = 0;
        $shipmentsCreated = 0;
        $byCategory = [];

        foreach ($groupedOrders as $category => $groupOrders) {
            $categoryOrders = $groupOrders->values();
            $byCategory[$category] = $categoryOrders->count();

            /** @var DeliveryOrder $firstOrder */
            $firstOrder = $categoryOrders->first();
            $transportTypeName = $this->resolveTransportType
                ->execute($firstOrder->purchase->offer->category->name)
                ->value;

            $invoices = [];
            foreach ($categoryOrders as $order) {
                $invoices[] = $this->buildInvoiceItem($order, GoLogOperationType::COLETA, $category, $transportTypeName, $scheduleDate);
                $invoices[] = $this->buildInvoiceItem($order, GoLogOperationType::ENTREGA, $category, $transportTypeName, $scheduleDate);
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

    private function buildInvoiceItem(
        DeliveryOrder $order,
        GoLogOperationType $operation,
        string $category,
        string $transportTypeName,
        CarbonImmutable $scheduleDate,
    ): GoLogInvoiceImportItemDTO {
        $isColeta = $operation === GoLogOperationType::COLETA;
        $purchase = $order->purchase;
        $producer = $purchase->producer->company;
        $customer = $isColeta ? $producer : $purchase->retailer->company;
        $address = $isColeta ? $order->pickupAddress : $order->dropoffAddress;
        $suffix = $isColeta ? 'COL' : 'ENT';
        $label = $isColeta ? 'Coleta' : 'Entrega';
        $deadlineHour = $isColeta ? 12 : 15;

        return new GoLogInvoiceImportItemDTO(
            invoiceNumber: "DO-{$order->id}-{$suffix}",
            series: '1',
            accessKey: 'NFE' . str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) . $suffix . $scheduleDate->format('Ymd'),
            typeOperation: $operation->value,
            weight: (float) $order->quantity,
            volume: round($order->quantity * 0.005, 3),
            value: (float) $purchase->total_price,
            scheduledDate: $scheduleDate->setTime($deadlineHour, 0)->utc()->toIso8601ZuluString(),
            notes: "[{$category}] {$label} Pedido #{$purchase->id} - {$purchase->product_name}",
            senderName: $producer->trade_name,
            senderDocument: $producer->document_number,
            customerName: $customer->trade_name,
            customerDocument: $customer->document_number,
            customerEmail: $customer->user?->email ?? '',
            customerPhone: $customer->phone,
            street: $address->street,
            number: $address->number,
            district: $address->neighborhood,
            city: $address->city,
            state: $address->state,
            cep: $address->zip,
            latitude: (string) $address->getAttribute('latitude'),
            longitude: (string) $address->getAttribute('longitude'),
            typeTransportName: $transportTypeName,
            shipmentTypeName: $category,
        );
    }
}
