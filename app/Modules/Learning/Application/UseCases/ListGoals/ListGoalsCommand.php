<?php

namespace App\Modules\Learning\Application\UseCases\ListGoals;

final readonly class ListGoalsCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
    ) {}
}
