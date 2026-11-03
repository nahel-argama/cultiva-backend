<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Actions\ResolveTransportTypeAction;
use Cultiva\Integrations\GoLog\Enums\GoLogTransportType;
use Cultiva\Models\Category\Enums\Category;
use Tests\TestCase;

class ResolveTransportTypeActionTest extends TestCase
{
    private ResolveTransportTypeAction $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new ResolveTransportTypeAction();
    }

    public function test_should_resolve_verduras_and_frutas_to_refrigerado(): void
    {
        $this->assertSame(GoLogTransportType::REFRIGERADO, $this->sut->execute(Category::VERDURAS));
        $this->assertSame(GoLogTransportType::REFRIGERADO, $this->sut->execute(Category::FRUTAS));
    }

    public function test_should_resolve_short_shelf_life_to_refrigerado_regardless_of_category(): void
    {
        $this->assertSame(GoLogTransportType::REFRIGERADO, $this->sut->execute(Category::GRAOS_E_CEREAIS, lifespanDays: 5));
    }

    public function test_should_resolve_legumes_to_climatizado(): void
    {
        $this->assertSame(GoLogTransportType::CLIMATIZADO, $this->sut->execute(Category::LEGUMES));
    }

    public function test_should_resolve_medium_shelf_life_to_climatizado(): void
    {
        $this->assertSame(GoLogTransportType::CLIMATIZADO, $this->sut->execute(Category::GRAOS_E_CEREAIS, lifespanDays: 12));
    }

    public function test_should_resolve_graos_and_tuberculos_to_carga_seca(): void
    {
        $this->assertSame(GoLogTransportType::CARGA_SECA, $this->sut->execute(Category::GRAOS_E_CEREAIS, lifespanDays: 60));
        $this->assertSame(GoLogTransportType::CARGA_SECA, $this->sut->execute(Category::TUBERCULOS_E_RAIZES, lifespanDays: 30));
    }
}
