<?php

namespace App\Modules\Library\Application\UseCases\ReadShared;

use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Entities\UserPhrase;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class ReadSharedUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    /**
     * @return list<UserEntry>
     */
    public function entries(string $userId, string $categoryId): ?array
    {
        $owner = $this->library->sharedOwner($categoryId, $userId);

        if ($owner === null) {
            return null;
        }

        return $this->library->listEntries($owner, null, $categoryId);
    }

    public function entry(string $userId, string $categoryId, string $entryId): ?UserEntry
    {
        $owner = $this->library->sharedOwner($categoryId, $userId);

        if ($owner === null) {
            return null;
        }

        $entry = $this->library->getEntry($owner, $entryId);

        if (! $entry || $entry->categoryId !== $categoryId) {
            return null;
        }

        return $entry;
    }

    /**
     * @return list<UserPhrase>
     */
    public function phrases(string $userId, string $categoryId): ?array
    {
        $owner = $this->library->sharedOwner($categoryId, $userId);

        if ($owner === null) {
            return null;
        }

        return $this->library->listPhrases($owner, null, $categoryId);
    }

    public function phrase(string $userId, string $categoryId, string $phraseId): ?UserPhrase
    {
        $owner = $this->library->sharedOwner($categoryId, $userId);

        if ($owner === null) {
            return null;
        }

        $phrase = $this->library->getPhrase($owner, $phraseId);

        if (! $phrase || $phrase->categoryId !== $categoryId) {
            return null;
        }

        return $phrase;
    }
}
