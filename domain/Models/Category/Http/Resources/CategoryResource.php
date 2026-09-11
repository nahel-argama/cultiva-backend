<?php

namespace Cultiva\Models\Category\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    /**
     * @return array{id: int, name: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
