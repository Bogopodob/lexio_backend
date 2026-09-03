<?php

namespace App\Modules\Library\Domain\Ports;

use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Entities\UserPhrase;

interface LibraryRepositoryInterface
{
    public function saveEntry(UserEntry $entry): UserEntry;

    /**
     * @return list<UserEntry>
     */
    public function listEntries(string $userId, ?string $languageId = null): array;

    public function savePhrase(UserPhrase $phrase): UserPhrase;

    /**
     * @return list<UserPhrase>
     */
    public function listPhrases(string $userId, ?string $languageId = null): array;
}
