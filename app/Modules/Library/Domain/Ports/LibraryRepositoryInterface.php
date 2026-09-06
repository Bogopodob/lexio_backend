<?php

namespace App\Modules\Library\Domain\Ports;

use App\Modules\Library\Domain\Entities\LibraryMedia;
use App\Modules\Library\Domain\Entities\LibraryShare;
use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Entities\UserPhrase;

interface LibraryRepositoryInterface
{
    public function saveEntry(UserEntry $entry): UserEntry;

    public function getEntry(string $userId, string $entryId): ?UserEntry;

    public function ownsEntry(string $userId, string $entryId): bool;

    public function deleteEntry(string $userId, string $entryId): bool;

    /**
     * @return list<UserEntry>
     */
    public function listEntries(string $userId, ?string $languageId = null, ?string $categoryId = null): array;

    public function savePhrase(UserPhrase $phrase): UserPhrase;

    public function getPhrase(string $userId, string $phraseId): ?UserPhrase;

    public function ownsPhrase(string $userId, string $phraseId): bool;

    public function deletePhrase(string $userId, string $phraseId): bool;

    /**
     * @return list<UserPhrase>
     */
    public function listPhrases(string $userId, ?string $languageId = null, ?string $categoryId = null): array;

    public function saveMedia(string $userId, string $kind, string $path, string $mime, int $bytes): LibraryMedia;

    public function findMedia(string $userId, string $mediaId): ?LibraryMedia;

    public function findMediaByPath(string $path): ?LibraryMedia;

    /**
     * A category's words are visible to its owner and to friends
     * the owner shared the category with. Everyone else sees nothing.
     */
    public function canAccessCategory(string $userId, string $categoryId): bool;

    public function shareCategory(string $ownerId, string $friendId, string $categoryId): LibraryShare;

    /**
     * @return list<LibraryShare>
     */
    public function sharesOfCategory(string $ownerId, string $categoryId): array;

    public function revokeShare(string $ownerId, string $shareId): bool;

    /**
     * Categories shared with the user: id, name, owner name, word counts.
     *
     * @return list<array{id: string, name: ?string, owner_name: ?string, words_count: int}>
     */
    public function sharedWithMe(string $userId, string $locale): array;

    /**
     * Owner of a category shared with the friend, null otherwise.
     * Own categories resolve to the user themselves.
     */
    public function sharedOwner(string $categoryId, string $friendId): ?string;
}
