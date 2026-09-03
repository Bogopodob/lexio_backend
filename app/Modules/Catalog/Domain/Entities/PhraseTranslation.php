<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class PhraseTranslation
{
    public function __construct(
        public string $id,
        public string $phraseId,
        public string $languageId,
        public string $text,
        public ?string $transcription,
        public ?string $audioPath,
    ) {}
}
