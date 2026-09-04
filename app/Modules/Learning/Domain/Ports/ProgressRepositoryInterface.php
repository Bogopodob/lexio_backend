<?php

namespace App\Modules\Learning\Domain\Ports;

use App\Modules\Learning\Domain\Entities\ReviewProgress;

interface ProgressRepositoryInterface
{
    public function find(string $profileId, string $learnableType, string $learnableId): ?ReviewProgress;

    public function save(ReviewProgress $progress): ReviewProgress;

    /**
     * @return list<ReviewProgress>
     */
    public function dueReviews(string $profileId, int $limit): array;

    public function countReviewed(string $profileId): int;

    public function countReviewedByType(string $profileId, string $learnableType): int;

    /**
     * @return array{total: int, correct: int} correct = quality_last >= 3
     */
    public function accuracyStats(string $profileId): array;

    /**
     * Learned entries (repetition > 0) grouped by catalog category.
     *
     * @return array<string, int> category_id => distinct entries count
     */
    public function countLearnedByCategory(string $profileId): array;
}
