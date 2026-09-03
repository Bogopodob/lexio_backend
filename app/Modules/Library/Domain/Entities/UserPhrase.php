<?php

namespace App\Modules\Library\Domain\Entities;

final readonly class UserPhrase
{
    /**
     * @param  list<UserPhraseTranslation>  $translations
     */
    public function __construct(
        public string $id,
        public string $userId,
        public ?string $categoryId,
        public string $phraseType,
        public array $translations = [],
    ) {}
}
