<?php

namespace App\Modules\Library\Application\UseCases\SaveUserPhrase;

use App\Modules\Library\Domain\Entities\UserPhrase;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class SaveUserPhraseUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function handle(SaveUserPhraseCommand $command): UserPhrase
    {
        return $this->library->savePhrase(new UserPhrase(
            id: '',
            userId: $command->userId,
            categoryId: $command->categoryId,
            phraseType: $command->phraseType,
            translations: $command->translations,
        ));
    }
}
