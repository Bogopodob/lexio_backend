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
}
