<?php

namespace App\Modules\Library\Application\UseCases\ListUserEntries;

final readonly class ListUserEntriesCommand
{
    public function __construct(
        public string $userId,
        public ?string $languageId = null,
    ) {}
}
