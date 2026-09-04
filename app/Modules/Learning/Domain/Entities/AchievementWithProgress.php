<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class AchievementWithProgress
{
    public function __construct(
        public Achievement $achievement,
        public int $progress,
        public bool $unlocked,
        public ?string $unlockedAt,
    ) {}
}
