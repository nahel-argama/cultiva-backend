<?php

namespace Cultiva\Models\Purchase\Resources;

use Cultiva\Models\Address\Transformers\AddressTransformer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $request->user()->isRetailer() ? 'producer' : 'retailer';
        $counterparty = $this->resource->{$role};
        $company = $counterparty->company;
        $category = $this->offer?->category;

        return [
            'id' => $this->id,
            'offer_id' => $this->offer_id,
            'retailer_id' => $this->retailer_id,
            'producer_id' => $this->producer_id,
            'source_product_id' => $this->source_product_id,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'created_at' => $this->created_at->toISOString(),
            'counterparty' => $company === null ? null : [
                'id' => $counterparty->id,
                'role' => $role,
                'trade_name' => $company->trade_name,
                'legal_name' => $company->legal_name,
                'document_number' => $company->document_number,
                'name' => $company->user->name,
                'email' => $company->user->email,
                'phone' => $company->phone,
                'address' => app(AddressTransformer::class)->transform($company->address),
            ],
            'category' => $category === null ? null : [
                'id' => $category->id,
                'name' => $category->name,
            ],
        ];
    }
}
