<?php

namespace App\Modules\Learning\Application\UseCases\WeeklyActivity;

final readonly class WeeklyActivityCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public int $days = 7,
    ) {}
}
