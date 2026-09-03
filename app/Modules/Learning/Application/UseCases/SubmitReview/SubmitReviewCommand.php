<?php

namespace App\Modules\Learning\Application\UseCases\SubmitReview;

final readonly class SubmitReviewCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public string $learnableType,
        public string $learnableId,
        public int $quality,
    ) {}
}
