<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class CatalogPhrase
{
    public function __construct(
        public string $id,
        public string $level,
        public string $phraseType,
        public ?string $imagePath,
    ) {}
}
