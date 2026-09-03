<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class ContentSource
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public ?string $languageId,
        public ?string $level,
        public ?string $externalId,
    ) {}
}
