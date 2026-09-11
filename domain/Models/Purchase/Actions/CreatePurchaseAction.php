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
            $offer = Offer::query()->lockForUpdate()->find($offerId);

            if (! $offer instanceof Offer) {
                throw new CultivaException(404, Lang::get('purchases.offer_not_found'));
            }

            $availableQuantity = $offer->total_quantity - $offer->reserved_quantity;
            if ($offer->status !== OfferStatus::ACTIVE || $data->quantity > $availableQuantity) {
                throw new CultivaException(409, Lang::get('purchases.insufficient_stock'));
            }

            $totalPrice = $this->calculateTotalPrice((string) $offer->unit_price, $data->quantity);
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

            $offer->update([
                'reserved_quantity' => $reservedQuantity,
                'status' => $reservedQuantity === $offer->total_quantity
                    ? OfferStatus::INACTIVE
                    : $offer->status,
            ]);

            return $purchase;
        });
    }

    private function calculateTotalPrice(string $unitPrice, int $quantity): string
    {
        [$whole, $decimal] = array_pad(explode('.', $unitPrice, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');

        return number_format(($cents * $quantity) / 100, 2, '.', '');
    }
}
