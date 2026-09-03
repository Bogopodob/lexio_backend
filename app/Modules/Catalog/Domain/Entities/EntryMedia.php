<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class EntryMedia
{
    public function __construct(
        public string $id,
        public ?string $entryId,
        public ?string $translationId,
        public string $type,
        public string $path,
        public string $source,
        public ?string $sourceTitle,
        public ?string $contentSourceId,
        public int $sort,
    ) {}
}
