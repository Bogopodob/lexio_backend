<?php

namespace App\Modules\Catalog\Application\UseCases\ListCategories;

final readonly class ListCategoriesCommand
{
    public function __construct(
        public ?string $type = null,
    ) {}
}
