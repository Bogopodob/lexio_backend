<?php

namespace App\Modules\Learning\Application\UseCases\SaveWordHint;

use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\WordHintRepositoryInterface;

final readonly class SaveWordHintUseCase
{
    public function __construct(
        private WordHintRepositoryInterface $hints,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return array{learnable_type: string, learnable_id: string, own_hint: ?string}|null
     */
    public function handle(SaveWordHintCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        if (! in_array($command->learnableType, ['entry', 'phrase', 'user_entry', 'user_phrase'], true)) {
            return null;
        }

        $hint = $this->hints->save(
            $command->profileId,
            $command->learnableType,
            $command->learnableId,
            $command->hint,
        );

        return [
            'learnable_type' => $command->learnableType,
            'learnable_id' => $command->learnableId,
            'own_hint' => $hint,
        ];
    }
}
