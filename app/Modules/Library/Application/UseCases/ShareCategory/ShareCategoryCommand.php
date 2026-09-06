<?php

namespace App\Modules\Library\Application\UseCases\ShareCategory;

final readonly class ShareCategoryCommand
{
    public function __construct(
        public string $userId,
        public string $categoryId,
        public string $friendId,
    ) {}
}
