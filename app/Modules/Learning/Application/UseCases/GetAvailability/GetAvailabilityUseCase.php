<?php

namespace App\Modules\Learning\Application\UseCases\GetAvailability;

use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;

final readonly class GetAvailabilityUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
        private StudySessionRepositoryInterface $sessions,
    ) {}

    /**
     * @return array{due: int, new: int}|null
     */
    public function handle(GetAvailabilityCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        return [
            'due' => $this->progress->countDue($command->profileId),
            'new' => $this->sessions->countNewEntries(
                $command->profileId,
                $command->categoryId,
                $command->level,
            ),
        ];
    }
}
