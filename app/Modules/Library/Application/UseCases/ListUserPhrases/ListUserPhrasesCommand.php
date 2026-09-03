<?php

namespace App\Modules\Library\Application\UseCases\ListUserPhrases;

final readonly class ListUserPhrasesCommand
{
    public function __construct(
        public string $userId,
        public ?string $languageId = null,
    ) {}
}
