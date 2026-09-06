<?php

namespace Cultiva\Models\Retailer\Transformers;

use Cultiva\Models\Retailer\Retailer;

final class RetailerTransformer
{
    public function transform(Retailer $retailer): array
    {
        return [
            'trade_name'    => $retailer->company->trade_name,
            'business_type' => $retailer->business_type->value,
        ];
    }
}
