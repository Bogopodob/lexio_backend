<?php

namespace App\Modules\Learning\Application\UseCases\SaveGoal;

final readonly class SaveGoalCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public ?string $goalId = null,
        public ?string $title = null,
        public ?string $desc = null,
        public ?string $color = null,
        public ?int $progress = null,
    ) {}
}
