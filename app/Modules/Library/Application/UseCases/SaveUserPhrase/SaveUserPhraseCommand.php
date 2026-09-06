<?php

namespace App\Modules\Library\Application\UseCases\SaveUserPhrase;

use App\Modules\Library\Domain\Entities\UserPhraseTranslation;

final readonly class SaveUserPhraseCommand
{
    /**
     * @param  list<UserPhraseTranslation>  $translations
     */
    public function __construct(
        public string $userId,
        public ?string $categoryId = null,
        public string $phraseType = 'phrase',
        public array $translations = [],
        public ?string $imagePath = null,
        public ?string $phraseId = null,
    ) {}
}
