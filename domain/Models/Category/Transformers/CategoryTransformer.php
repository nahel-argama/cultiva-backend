<?php

namespace Cultiva\Models\Category\Transformers;

use Cultiva\Models\Category\Category;

final class CategoryTransformer
{
    public function transform(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
        ];
    }
}
