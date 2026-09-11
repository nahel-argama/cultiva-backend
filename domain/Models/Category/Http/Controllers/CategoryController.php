<?php

namespace Cultiva\Models\Category\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Category\Actions\ListCategoriesAction;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Category\Transformers\CategoryTransformer;
use Illuminate\Http\JsonResponse;

final class CategoryController extends Controller
{
    public function index(
        ListCategoriesAction $action,
        CategoryTransformer $transformer,
    ): JsonResponse {
        $categories = $action->execute();

        return response()->json([
            'data' => $categories
                ->map(fn (Category $category): array => $transformer->transform($category))
                ->all(),
        ]);
    }
}
