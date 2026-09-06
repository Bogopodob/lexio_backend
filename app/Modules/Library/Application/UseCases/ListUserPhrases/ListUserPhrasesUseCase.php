<?php

namespace App\Modules\Library\Application\UseCases\ListUserPhrases;

use App\Modules\Library\Domain\Entities\UserPhrase;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class ListUserPhrasesUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    /**
     * @return list<UserPhrase>
     */
    public function handle(ListUserPhrasesCommand $command): array
    {
        return $this->library->listPhrases($command->userId, $command->languageId, $command->categoryId);
    }
}
