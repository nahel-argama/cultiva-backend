<?php

namespace Cultiva\Models\Category\Actions;

use Cultiva\Models\Category\Category;
use Illuminate\Database\Eloquent\Collection;

final class ListCategoriesAction
{
    /**
     * @return Collection<int, Category>
     */
    public function execute(): Collection
    {
        return Category::query()->orderBy('id')->get();
    }
}
