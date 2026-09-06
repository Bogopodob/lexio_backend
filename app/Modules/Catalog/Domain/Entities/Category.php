<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class Category
{
    public function __construct(
        public string $id,
        public ?string $parentId,
        public ?string $userId,
        public string $slug,
        public bool $isSystem,
        public string $type,
        public ?string $color,
        public ?string $icon,
        public int $sort,
        public ?string $name = null,
        public int $entriesCount = 0,
        public int $phrasesCount = 0,
    ) {}
}
