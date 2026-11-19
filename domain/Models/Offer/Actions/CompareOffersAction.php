<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\DTO\OfferComparisonDTO;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Purchase\Purchase;
use Cultiva\Models\User\User;
use Illuminate\Validation\ValidationException;

final class CompareOffersAction
{
    public function execute(User $user, int $firstId, int $secondId): OfferComparisonDTO
    {
        $user->loadMissing('company.address');
        $coordinate = $user->company->address->coordinate;
        $recentSales = Purchase::query()
            ->selectRaw('count(*)')
            ->whereColumn('purchases.producer_id', 'offers.producer_id')
            ->where('purchases.created_at', '>=', now()->subDays(90));

        $offers = Offer::query()
            ->with(['category', 'producer.company'])
            ->select('offers.*')
            ->join('producers', 'producers.id', '=', 'offers.producer_id')
            ->leftJoin('addresses as producer_addresses', 'producer_addresses.company_id', '=', 'producers.company_id')
            ->whereNull('producers.deleted_at')
            ->where('offers.status', OfferStatus::ACTIVE->value)
            ->whereColumn('offers.total_quantity', '>', 'offers.reserved_quantity')
            ->selectRaw(
                'ROUND((ST_Distance(producer_addresses.coordinate, ?::geography) / 1000.0)::numeric, 2)::float as distance_km',
                [$coordinate],
            )
            ->selectSub($recentSales, 'recent_sales')
            ->findOrFail([$firstId, $secondId])
            ->keyBy('id');

        $first = $offers[$firstId];
        $second = $offers[$secondId];

        if ($first->source_product_id !== $second->source_product_id) {
            throw ValidationException::withMessages([
                'second_offer_id' => 'Escolha duas ofertas do mesmo produto.',
            ]);
        }

        $criteria = [
            'unit_price' => $this->winner($first->unit_price, $second->unit_price, $secondId, $firstId),
            'distance_km' => $this->winner($first->distance_km, $second->distance_km, $secondId, $firstId),
            'available_quantity' => $this->winner($first->availableQuantity(), $second->availableQuantity(), $firstId, $secondId),
            'recent_sales' => $this->winner($first->recent_sales, $second->recent_sales, $firstId, $secondId),
        ];

        $points = array_count_values(array_filter($criteria));
        $recommended = $this->winner($points[$firstId] ?? 0, $points[$secondId] ?? 0, $firstId, $secondId);

        return new OfferComparisonDTO($offers->sortBy(fn (Offer $offer) => $offer->id === $firstId ? 0 : 1)->values(), $criteria, $recommended);
    }

    private function winner(int|float|string|null $first, int|float|string|null $second, int $higherId, int $lowerId): ?int
    {
        if ($first === null || $second === null) {
            return null;
        }

        return match ($first <=> $second) {
            1 => $higherId,
            -1 => $lowerId,
            default => null,
        };
    }
}
