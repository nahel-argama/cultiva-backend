<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Enums\GoLogTransportType;
use Cultiva\Models\Category\Enums\Category;

final class ResolveTransportTypeAction
{
    /**
     * Resolve GoLog transport type based on product category and optional shelf lifespan in days.
     */
    public function execute(Category $category, ?int $lifespanDays = null): GoLogTransportType
    {
        if (
            $category === Category::VERDURAS
            || $category === Category::FRUTAS
            || ($lifespanDays !== null && $lifespanDays <= 7)
        ) {
            return GoLogTransportType::REFRIGERADO;
        }

        if (
            $category === Category::LEGUMES
            || ($lifespanDays !== null && $lifespanDays <= 15)
        ) {
            return GoLogTransportType::CLIMATIZADO;
        }

        return GoLogTransportType::CARGA_SECA;
    }
}
