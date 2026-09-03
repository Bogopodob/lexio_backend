<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class CatalogEntry
{
    public function __construct(
        public string $id,
        public string $level,
        public ?string $imagePath,
        public ?int $frequencyRank,
    ) {}
}
