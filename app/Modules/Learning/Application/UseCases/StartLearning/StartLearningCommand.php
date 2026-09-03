<?php

namespace App\Modules\Learning\Application\UseCases\StartLearning;

final readonly class StartLearningCommand
{
    public function __construct(
        public string $userId,
        public string $targetLanguageId,
        public string $nativeLanguageId,
        public string $level = 'A2',
        public int $dailyGoal = 10,
    ) {}
}
