<?php

namespace Cultiva\Models\Purchase\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Purchase\DTO\CreatePurchaseDTO;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Cultiva\Models\Purchase\Purchase;
use Cultiva\Models\Retailer\Retailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

final class CreatePurchaseAction
{
    public function execute(Retailer $retailer, int $offerId, CreatePurchaseDTO $data): Purchase
    {
        $purchase = DB::transaction(function () use ($retailer, $offerId, $data): Purchase {
            $offer = Offer::query()->lockForUpdate()->findOrFail($offerId);

            $availableQuantity = $offer->availableQuantity();

            if ($offer->status !== OfferStatus::ACTIVE || $data->quantity > $availableQuantity) {
                throw new CultivaException(409, Lang::get('purchases.insufficient_stock'));
            }

            if ($data->expectedUnitPrice !== null && (int) round($data->expectedUnitPrice * 100) !== (int) round($offer->unit_price * 100)) {
                throw new CultivaException(409, 'O preço da oferta mudou. Revise o pedido antes de continuar.');
            }

            $totalPrice = $this->calculateTotalPrice($offer->unit_price, $data->quantity);
            $reservedQuantity = $offer->reserved_quantity + $data->quantity;

            $purchase = Purchase::create([
                'offer_id' => $offer->id,
                'retailer_id' => $retailer->id,
                'producer_id' => $offer->producer_id,
                'source_product_id' => $offer->source_product_id,
                'product_name' => $offer->product_name,
                'quantity' => $data->quantity,
                'unit_price' => $offer->unit_price,
                'total_price' => $totalPrice,
            ]);

            $newStatus = $reservedQuantity === $offer->total_quantity
                ? OfferStatus::INACTIVE
                : $offer->status;

            $offer->update([
                'reserved_quantity' => $reservedQuantity,
                'status' => $newStatus,
            ]);

            return $purchase;
        });

        event(new PurchaseCreated($purchase));

        return $purchase->load(['producer.company.user', 'producer.company.address', 'offer.category']);
    }

    private function calculateTotalPrice(float $unitPrice, int $quantity): float
    {
        $cents = (int) round($unitPrice * 100);

        $subtotal = $cents * $quantity;

        return ($subtotal + (int) round($subtotal * 0.05)) / 100;
    }
}
