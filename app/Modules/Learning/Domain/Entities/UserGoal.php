<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class UserGoal
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $profileId,
        public string $title,
        public ?string $desc,
        public ?string $color,
        public int $progress,
        public int $sort,
    ) {}
}
