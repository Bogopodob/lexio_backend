<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class LanguageProfile
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $targetLanguageId,
        public string $nativeLanguageId,
        public string $level,
        public int $dailyGoal,
        public bool $isActive,
    ) {}
}
