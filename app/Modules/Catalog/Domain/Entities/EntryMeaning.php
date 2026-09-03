<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class EntryMeaning
{
    public function __construct(
        public string $id,
        public string $entryId,
        public ?string $note,
    ) {}
}
