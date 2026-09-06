<?php

namespace App\Modules\Library\Application\UseCases\ListUserEntries;

use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class ListUserEntriesUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    /**
     * @return list<UserEntry>
     */
    public function handle(ListUserEntriesCommand $command): array
    {
        return $this->library->listEntries($command->userId, $command->languageId, $command->categoryId);
    }
}
