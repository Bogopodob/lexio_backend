<?php

namespace App\Modules\Library\Application\UseCases\SaveUserPhrase;

use App\Modules\Library\Domain\Entities\UserPhrase;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class SaveUserPhraseUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function show(string $userId, string $phraseId): ?UserPhrase
    {
        return $this->library->getPhrase($userId, $phraseId);
    }

    public function destroy(string $userId, string $phraseId): bool
    {
        return $this->library->deletePhrase($userId, $phraseId);
    }

    public function handle(SaveUserPhraseCommand $command): ?UserPhrase
    {
        $id = '';

        if ($command->phraseId !== null) {
            // Update path: only the owner's own phrases (shared ones are read-only).
            if (! $this->library->ownsPhrase($command->userId, $command->phraseId)) {
                return null;
            }

            $id = $command->phraseId;
        }

        return $this->library->savePhrase(new UserPhrase(
            id: $id,
            userId: $command->userId,
            categoryId: $command->categoryId,
            phraseType: $command->phraseType,
            translations: $command->translations,
            imagePath: $command->imagePath,
        ));
    }
}
