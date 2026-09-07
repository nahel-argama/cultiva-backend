<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Base\Traits;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Delivery\Enums\CnhCategory;
use Cultiva\Models\Producer\Enums\ActivitySegment;
use Cultiva\Models\Retailer\Enums\BusinessType;
use Cultiva\Models\Vehicle\Enums\CargoType;
use Tests\TestCase;

class HasEnumLabelTest extends TestCase
{
    public function test_should_return_label_for_profile_type(): void
    {
        $this->assertSame('Produtor', ProfileType::PRODUCER->getLabel());
        $this->assertSame('Varejista', ProfileType::RETAILER->getLabel());
        $this->assertSame('Entregador', ProfileType::DELIVERY->getLabel());
    }

    public function test_should_return_options_for_all_signup_enums(): void
    {
        $profileTypes = ProfileType::options();
        $this->assertSame([
            'producer' => 'Produtor',
            'retailer' => 'Varejista',
            'delivery' => 'Entregador',
        ], $profileTypes);

        $activitySegments = ActivitySegment::options();
        $this->assertArrayHasKey('vegetables', $activitySegments);
        $this->assertSame('Hortaliças', $activitySegments['vegetables']);

        $businessTypes = BusinessType::options();
        $this->assertArrayHasKey('supermarket', $businessTypes);
        $this->assertSame('Supermercado', $businessTypes['supermarket']);

        $cnhCategories = CnhCategory::options();
        $this->assertArrayHasKey('A', $cnhCategories);
        $this->assertSame('Categoria A', $cnhCategories['A']);

        $cargoTypes = CargoType::options();
        $this->assertSame([
            'dry'                => 'Carga Seca',
            'climate_controlled' => 'Climatizada',
            'refrigerated'       => 'Refrigerada',
        ], $cargoTypes);
    }
}
