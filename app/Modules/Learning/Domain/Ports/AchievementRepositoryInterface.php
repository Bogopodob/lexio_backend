<?php

namespace App\Modules\Learning\Domain\Ports;

use App\Modules\Learning\Domain\Entities\Achievement;
use App\Modules\Learning\Domain\Entities\AchievementWithProgress;

interface AchievementRepositoryInterface
{
    /**
     * @return list<Achievement>
     */
    public function all(): array;

    /**
     * @return list<AchievementWithProgress>
     */
    public function listWithProgress(string $userId, string $profileId): array;

    public function saveProgress(
        string $userId,
        string $profileId,
        string $achievementId,
        int $progress,
        bool $unlocked,
        ?string $unlockedAt,
    ): void;
}
