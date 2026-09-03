<?php

namespace App\Modules\Learning\Application\UseCases\DueReviews;

final readonly class DueReviewsCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public int $limit = 20,
    ) {}
}
