<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Enums\GoLogTransportType;
use Cultiva\Models\Category\Enums\Category;

final class ResolveTransportTypeAction
{
    public function execute(Category $category): GoLogTransportType
    {
        return match ($category) {
            Category::VERDURAS, Category::FRUTAS => GoLogTransportType::REFRIGERADO,
            Category::LEGUMES => GoLogTransportType::CLIMATIZADO,
            Category::TUBERCULOS_E_RAIZES, Category::GRAOS_E_CEREAIS => GoLogTransportType::CARGA_SECA,
        };
    }
}
