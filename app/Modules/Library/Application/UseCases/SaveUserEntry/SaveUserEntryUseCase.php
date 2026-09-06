<?php

namespace App\Modules\Library\Application\UseCases\SaveUserEntry;

use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class SaveUserEntryUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function show(string $userId, string $entryId): ?UserEntry
    {
        return $this->library->getEntry($userId, $entryId);
    }

    public function destroy(string $userId, string $entryId): bool
    {
        return $this->library->deleteEntry($userId, $entryId);
    }

    public function handle(SaveUserEntryCommand $command): ?UserEntry
    {
        $id = '';

        if ($command->entryId !== null) {
            // Update path: foreign entries are untouchable.
            if ($this->library->getEntry($command->userId, $command->entryId) === null) {
                return null;
            }

            $id = $command->entryId;
        }

        return $this->library->saveEntry(new UserEntry(
            id: $id,
            userId: $command->userId,
            categoryId: $command->categoryId,
            imagePath: $command->imagePath,
            translations: $command->translations,
        ));
    }
}
