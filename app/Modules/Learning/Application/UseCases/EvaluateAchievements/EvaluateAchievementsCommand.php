<?php

namespace App\Modules\Learning\Application\UseCases\EvaluateAchievements;

final readonly class EvaluateAchievementsCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
    ) {}
}
