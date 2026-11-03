<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Models\Category\Enums;

use Cultiva\Models\Category\Category as CategoryModel;
use Cultiva\Models\Category\Enums\Category;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function test_should_have_exact_expected_cases_and_values(): void
    {
        $this->assertSame('Frutas', Category::FRUTAS->value);
        $this->assertSame('Legumes', Category::LEGUMES->value);
        $this->assertSame('Verduras', Category::VERDURAS->value);
        $this->assertSame('Tubérculos e raízes', Category::TUBERCULOS_E_RAIZES->value);
        $this->assertSame('Grãos e cereais', Category::GRAOS_E_CEREAIS->value);

        $this->assertCount(5, Category::cases());
    }

    public function test_should_return_correct_labels(): void
    {
        $this->assertSame('Frutas', Category::FRUTAS->getLabel());
        $this->assertSame('Legumes', Category::LEGUMES->getLabel());
        $this->assertSame('Verduras', Category::VERDURAS->getLabel());
        $this->assertSame('Tubérculos e raízes', Category::TUBERCULOS_E_RAIZES->getLabel());
        $this->assertSame('Grãos e cereais', Category::GRAOS_E_CEREAIS->getLabel());
    }

    public function test_should_return_all_options(): void
    {
        $options = Category::options();

        $this->assertSame([
            'Frutas' => 'Frutas',
            'Legumes' => 'Legumes',
            'Verduras' => 'Verduras',
            'Tubérculos e raízes' => 'Tubérculos e raízes',
            'Grãos e cereais' => 'Grãos e cereais',
        ], $options);
    }

    public function test_model_casts_name_attribute_to_category_enum(): void
    {
        $modelFromString = new CategoryModel(['name' => 'Legumes']);
        $this->assertSame(Category::LEGUMES, $modelFromString->name);

        $modelFromEnum = new CategoryModel(['name' => Category::VERDURAS]);
        $this->assertSame(Category::VERDURAS, $modelFromEnum->name);
    }
}
