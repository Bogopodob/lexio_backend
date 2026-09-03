<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class EntryTranslation
{
    public function __construct(
        public string $id,
        public string $entryId,
        public string $meaningId,
        public string $languageId,
        public string $text,
        public ?string $transcription,
        public ?string $audioPath,
        public ?string $partOfSpeech,
    ) {}
}
