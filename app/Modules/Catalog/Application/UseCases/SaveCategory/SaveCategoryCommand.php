<?php

namespace App\Modules\Catalog\Application\UseCases\SaveCategory;

final readonly class SaveCategoryCommand
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $locale = 'ru',
        public ?string $categoryId = null,
        public ?string $color = null,
        public ?string $icon = null,
        public ?string $parentId = null,
    ) {}
}
