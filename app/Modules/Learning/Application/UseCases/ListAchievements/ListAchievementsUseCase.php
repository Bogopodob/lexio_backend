<?php

namespace App\Modules\Learning\Application\UseCases\ListAchievements;

use App\Modules\Learning\Domain\Entities\AchievementWithProgress;
use App\Modules\Learning\Domain\Ports\AchievementRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class ListAchievementsUseCase
{
    public function __construct(
        private AchievementRepositoryInterface $achievements,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return list<AchievementWithProgress>|null
     */
    public function handle(ListAchievementsCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        return $this->achievements->listWithProgress($command->userId, $command->profileId);
    }
}
