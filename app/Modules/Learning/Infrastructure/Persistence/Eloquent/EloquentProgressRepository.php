<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Entities\ReviewProgress;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserProgress as ProgressModel;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

final class EloquentProgressRepository implements ProgressRepositoryInterface
{
    public function find(string $profileId, string $learnableType, string $learnableId): ?ReviewProgress
    {
        $model = ProgressModel::query()
            ->where('profile_id', $profileId)
            ->where('learnable_type', $learnableType)
            ->where('learnable_id', $learnableId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(ReviewProgress $progress): ReviewProgress
    {
        $model = ProgressModel::query()->updateOrCreate(
            ['id' => $progress->id !== '' ? $progress->id : Uuid::uuid4()->toString()],
            [
                'user_id' => $progress->userId,
                'profile_id' => $progress->profileId,
                'learnable_type' => $progress->learnableType,
                'learnable_id' => $progress->learnableId,
                'easiness_factor' => $progress->easinessFactor,
                'interval_days' => $progress->intervalDays,
                'repetition' => $progress->repetition,
                'quality_last' => $progress->qualityLast,
                'next_review_at' => $progress->nextReviewAt,
                'last_reviewed_at' => $progress->lastReviewedAt,
            ],
        );

        return $this->toDomain($model);
    }

    public function dueReviews(string $profileId, int $limit): array
    {
        return ProgressModel::query()
            ->where('profile_id', $profileId)
            ->where(function ($q) {
                $q->whereNull('next_review_at')->orWhere('next_review_at', '<=', Carbon::now());
            })
            ->orderBy('next_review_at')
            ->limit($limit)
            ->get()
            ->map(fn (ProgressModel $m) => $this->toDomain($m))
            ->all();
    }

    public function countReviewed(string $profileId): int
    {
        return ProgressModel::query()->where('profile_id', $profileId)->count();
    }

    public function countReviewedByType(string $profileId, string $learnableType): int
    {
        return ProgressModel::query()
            ->where('profile_id', $profileId)
            ->where('learnable_type', $learnableType)
            ->count();
    }

    public function accuracyStats(string $profileId): array
    {
        $total = ProgressModel::query()->where('profile_id', $profileId)->count();
        $correct = ProgressModel::query()
            ->where('profile_id', $profileId)
            ->where('quality_last', '>=', 3)
            ->count();

        return ['total' => $total, 'correct' => $correct];
    }

    private function toDomain(ProgressModel $m): ReviewProgress
    {
        return new ReviewProgress(
            id: (string) $m->id,
            userId: (string) $m->user_id,
            profileId: (string) $m->profile_id,
            learnableType: $m->learnable_type,
            learnableId: (string) $m->learnable_id,
            easinessFactor: (float) $m->easiness_factor,
            intervalDays: (int) $m->interval_days,
            repetition: (int) $m->repetition,
            qualityLast: (int) $m->quality_last,
            nextReviewAt: $m->next_review_at ? (string) $m->next_review_at : null,
            lastReviewedAt: $m->last_reviewed_at ? (string) $m->last_reviewed_at : null,
        );
    }
}
