<?php

namespace App\Modules\Learning\Domain\Ports;

use App\Modules\Learning\Domain\Entities\Streak;

interface StreakRepositoryInterface
{
    public function findDay(string $userId, ?string $profileId, string $date): ?Streak;

    public function save(Streak $streak): Streak;

    /**
     * Recent days with activity, newest first.
     *
     * @return list<Streak>
     */
    public function recentActiveDays(string $userId, ?string $profileId, int $limit): array;
}
