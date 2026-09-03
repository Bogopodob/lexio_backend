<?php

namespace App\Modules\Library\Application\UseCases\SaveUserEntry;

use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class SaveUserEntryUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function handle(SaveUserEntryCommand $command): UserEntry
    {
        return $this->library->saveEntry(new UserEntry(
            id: '',
            userId: $command->userId,
            categoryId: $command->categoryId,
            imagePath: $command->imagePath,
            translations: $command->translations,
        ));
    }
}
