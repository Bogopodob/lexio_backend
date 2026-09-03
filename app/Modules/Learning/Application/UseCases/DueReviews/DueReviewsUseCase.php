<?php

namespace App\Modules\Learning\Application\UseCases\DueReviews;

use App\Modules\Learning\Domain\Entities\ReviewProgress;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;

final readonly class DueReviewsUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
    ) {}

    /**
     * @return list<ReviewProgress>|null
     */
    public function handle(DueReviewsCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        return $this->progress->dueReviews($command->profileId, $command->limit);
    }
}
