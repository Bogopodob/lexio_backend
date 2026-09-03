<?php

namespace App\Modules\Catalog\Infrastructure\Http\Resources;

use App\Modules\Catalog\Domain\Entities\Category;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    public function __construct(private readonly Category $category)
    {
        parent::__construct($category);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->category->id,
            'parent_id' => $this->category->parentId,
            'slug' => $this->category->slug,
            'is_system' => $this->category->isSystem,
            'type' => $this->category->type,
            'color' => $this->category->color,
            'icon' => $this->category->icon,
            'sort' => $this->category->sort,
        ];
    }
}
