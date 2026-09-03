<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class LanguageStat
{
    public function __construct(
        public string $id,
        public string $profileId,
        public int $wordsLearned,
        public int $streakDays,
        public int $bestStreak,
        public int $xp,
        public float $accuracy,
        public ?string $lastActivityAt,
    ) {}
}
