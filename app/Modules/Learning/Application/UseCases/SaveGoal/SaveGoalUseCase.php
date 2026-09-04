<?php

namespace App\Modules\Learning\Application\UseCases\SaveGoal;

use App\Modules\Learning\Domain\Entities\UserGoal;
use App\Modules\Learning\Domain\Ports\GoalRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class SaveGoalUseCase
{
    private const MAX_GOALS = 3;

    public function __construct(
        private GoalRepositoryInterface $goals,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    public function handle(SaveGoalCommand $command): ?UserGoal
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        if ($command->goalId !== null) {
            $existing = $this->goals->find($command->goalId);

            if (! $existing || $existing->profileId !== $command->profileId) {
                return null;
            }

            return $this->goals->save(new UserGoal(
                id: $existing->id,
                userId: $existing->userId,
                profileId: $existing->profileId,
                title: $command->title ?? $existing->title,
                desc: $command->desc ?? $existing->desc,
                color: $command->color ?? $existing->color,
                progress: $command->progress ?? $existing->progress,
                sort: $existing->sort,
            ));
        }

        if ($command->title === null || trim($command->title) === '') {
            return null;
        }

        if ($this->goals->countByProfile($command->profileId) >= self::MAX_GOALS) {
            return null;
        }

        return $this->goals->save(new UserGoal(
            id: '',
            userId: $command->userId,
            profileId: $command->profileId,
            title: $command->title,
            desc: $command->desc,
            color: $command->color,
            progress: $command->progress ?? 0,
            sort: 500,
        ));
    }
}
