<?php

namespace App\Modules\Learning\Application\UseCases\DeleteGoal;

use App\Modules\Learning\Domain\Ports\GoalRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class DeleteGoalUseCase
{
    public function __construct(
        private GoalRepositoryInterface $goals,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    public function handle(DeleteGoalCommand $command): bool
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return false;
        }

        $existing = $this->goals->find($command->goalId);

        if (! $existing || $existing->profileId !== $command->profileId) {
            return false;
        }

        $this->goals->delete($command->goalId);

        return true;
    }
}
