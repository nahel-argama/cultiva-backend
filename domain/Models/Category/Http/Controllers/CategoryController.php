<?php

namespace Cultiva\Models\Category\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Category\Actions\ListCategoriesAction;
use Cultiva\Models\Category\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    public function index(
        ListCategoriesAction $action,
    ): AnonymousResourceCollection {
        return CategoryResource::collection($action->execute());
    }
}
