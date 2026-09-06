<?php

namespace App\Modules\Library\Domain\Entities;

final readonly class UserEntryTranslation
{
    public function __construct(
        public string $id,
        public string $languageId,
        public string $text,
        public ?string $transcription,
        public ?string $partOfSpeech,
        public ?string $notes,
        public ?string $audioPath = null,
    ) {}
}
