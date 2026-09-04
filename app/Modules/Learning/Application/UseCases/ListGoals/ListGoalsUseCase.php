<?php

namespace App\Modules\Learning\Application\UseCases\ListGoals;

use App\Modules\Learning\Domain\Entities\UserGoal;
use App\Modules\Learning\Domain\Ports\GoalRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class ListGoalsUseCase
{
    public function __construct(
        private GoalRepositoryInterface $goals,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return list<UserGoal>|null
     */
    public function handle(ListGoalsCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        return $this->goals->listByProfile($command->profileId);
    }
}
