<?php

namespace Cultiva\Models\DeliveryOrder\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\Purchase\Purchase;
use Illuminate\Support\Facades\DB;

class CreateDeliveryOrderAction
{
    public function execute(Purchase $purchase): DeliveryOrder
    {
        $purchase->loadMissing([
            'producer.company.address',
            'retailer.company.address',
        ]);

        $producerAddress = $purchase->producer?->company?->address;
        $retailerAddress = $purchase->retailer?->company?->address;

        if (! $producerAddress || ! $retailerAddress) {
            throw new CultivaException(422, 'Cannot create delivery order without pickup and dropoff addresses.');
        }

        return DB::transaction(function () use ($purchase, $producerAddress, $retailerAddress): DeliveryOrder {
            $pickupDeliveryAddress = DeliveryAddress::create([
                'zip' => $producerAddress->zip,
                'street' => $producerAddress->street,
                'number' => $producerAddress->number,
                'complement' => $producerAddress->complement,
                'reference_point' => $producerAddress->reference_point,
                'neighborhood' => $producerAddress->neighborhood,
                'city' => $producerAddress->city,
                'state' => $producerAddress->state,
                'coordinate' => $producerAddress->coordinate,
            ]);

            $dropoffDeliveryAddress = DeliveryAddress::create([
                'zip' => $retailerAddress->zip,
                'street' => $retailerAddress->street,
                'number' => $retailerAddress->number,
                'complement' => $retailerAddress->complement,
                'reference_point' => $retailerAddress->reference_point,
                'neighborhood' => $retailerAddress->neighborhood,
                'city' => $retailerAddress->city,
                'state' => $retailerAddress->state,
                'coordinate' => $retailerAddress->coordinate,
            ]);

            return DeliveryOrder::create([
                'purchase_id' => $purchase->id,
                'pickup_address_id' => $pickupDeliveryAddress->id,
                'dropoff_address_id' => $dropoffDeliveryAddress->id,
                'quantity' => $purchase->quantity,
                'status' => DeliveryOrderStatus::PENDING,
            ]);
        });
    }
}
