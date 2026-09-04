<?php

namespace App\Modules\Learning\Application\UseCases\ListAchievements;

final readonly class ListAchievementsCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
    ) {}
}
