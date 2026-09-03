<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class Streak
{
    public function __construct(
        public string $id,
        public string $userId,
        public ?string $profileId,
        public string $date,
        public int $wordsReviewed,
        public int $wordsNew,
    ) {}
}
