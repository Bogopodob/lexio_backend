<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class ReviewProgress
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $profileId,
        public string $learnableType,
        public string $learnableId,
        public float $easinessFactor,
        public int $intervalDays,
        public int $repetition,
        public int $qualityLast,
        public ?string $nextReviewAt,
        public ?string $lastReviewedAt,
    ) {}
}
