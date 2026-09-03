<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class EntrySearchHit
{
    public function __construct(
        public string $entryId,
        public string $level,
        public ?string $imagePath,
        public string $matchedText,
        public string $languageId,
    ) {}
}
