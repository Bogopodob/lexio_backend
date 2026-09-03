<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class CatalogLanguage
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $nativeName,
        public string $direction,
        public bool $isActive,
        public int $sort,
    ) {}
}
