<?php

namespace Cultiva\Models\Purchase\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Purchase\DTO\CreatePurchaseDTO;
use Cultiva\Models\Purchase\Purchase;
use Cultiva\Models\Retailer\Retailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

final class CreatePurchaseAction
{
    public function execute(Retailer $retailer, int $offerId, CreatePurchaseDTO $data): Purchase
    {
        return DB::transaction(function () use ($retailer, $offerId, $data): Purchase {
            $offer = Offer::query()->lockForUpdate()->findOrFail($offerId);

            $availableQuantity = $offer->availableQuantity();

            if ($offer->status !== OfferStatus::ACTIVE || $data->quantity > $availableQuantity) {
                throw new CultivaException(409, Lang::get('purchases.insufficient_stock'));
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
    }

    private function calculateTotalPrice(float $unitPrice, int $quantity): float
    {
        $cents = (int) round($unitPrice * 100);

        return ($cents * $quantity) / 100;
    }
}
