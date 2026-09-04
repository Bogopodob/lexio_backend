<?php

namespace App\Modules\Learning\Application\UseCases\DeleteGoal;

final readonly class DeleteGoalCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public string $goalId,
    ) {}
}
