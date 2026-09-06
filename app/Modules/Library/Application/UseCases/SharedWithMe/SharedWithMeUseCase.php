<?php

namespace App\Modules\Library\Application\UseCases\SharedWithMe;

use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class SharedWithMeUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    /**
     * @return list<array{id: string, name: ?string, owner_name: ?string, words_count: int}>
     */
    public function handle(SharedWithMeCommand $command): array
    {
        return $this->library->sharedWithMe($command->userId, $command->locale);
    }
}
