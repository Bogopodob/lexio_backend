<?php

namespace App\Modules\Catalog\Application\UseCases\DeleteCategory;

final readonly class DeleteCategoryCommand
{
    public function __construct(
        public string $userId,
        public string $categoryId,
    ) {}
}
