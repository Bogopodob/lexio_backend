<?php

namespace App\Modules\Library\Domain\Ports;

use App\Modules\Library\Domain\Entities\LibraryMedia;
use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Entities\UserPhrase;

interface LibraryRepositoryInterface
{
    public function saveEntry(UserEntry $entry): UserEntry;

    public function getEntry(string $userId, string $entryId): ?UserEntry;

    public function deleteEntry(string $userId, string $entryId): bool;

    /**
     * @return list<UserEntry>
     */
    public function listEntries(string $userId, ?string $languageId = null, ?string $categoryId = null): array;

    public function savePhrase(UserPhrase $phrase): UserPhrase;

    public function getPhrase(string $userId, string $phraseId): ?UserPhrase;

    public function deletePhrase(string $userId, string $phraseId): bool;

    /**
     * @return list<UserPhrase>
     */
    public function listPhrases(string $userId, ?string $languageId = null, ?string $categoryId = null): array;

    public function saveMedia(string $userId, string $kind, string $path, string $mime, int $bytes): LibraryMedia;

    public function findMedia(string $userId, string $mediaId): ?LibraryMedia;

    public function findMediaByPath(string $path): ?LibraryMedia;
}
