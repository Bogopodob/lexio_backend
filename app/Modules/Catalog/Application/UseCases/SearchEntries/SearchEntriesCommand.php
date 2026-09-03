<?php

namespace App\Modules\Catalog\Application\UseCases\SearchEntries;

final readonly class SearchEntriesCommand
{
    public function __construct(
        public string $languageId,
        public string $query,
        public ?string $level = null,
        public int $limit = 20,
    ) {}
}
