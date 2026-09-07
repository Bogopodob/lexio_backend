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

    public function countDue(string $profileId): int
    {
        return ProgressModel::query()
            ->where('profile_id', $profileId)
            ->where(function ($q) {
                $q->whereNull('next_review_at')->orWhere('next_review_at', '<=', Carbon::now());
            })
            ->count();
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

    public function countLearnedByCategory(string $profileId): array
    {
        $rows = ProgressModel::query()
            ->join('entry_category', function ($join) {
                $join->on('entry_category.entry_id', '=', 'user_progresses.learnable_id')
                    ->where('user_progresses.learnable_type', '=', 'entry');
            })
            ->where('user_progresses.profile_id', $profileId)
            ->where('user_progresses.repetition', '>', 0)
            ->selectRaw('entry_category.category_id, COUNT(DISTINCT user_progresses.learnable_id) as total')
            ->groupBy('entry_category.category_id')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row->category_id] = (int) $row->total;
        }

        // Catalog phrases (blocks like "Restaurant") live in phrases_categories,
        // so without this branch phrase bands would show 0% forever.
        $phraseRows = ProgressModel::query()
            ->join('phrases_categories', function ($join) {
                $join->on('phrases_categories.phrase_id', '=', 'user_progresses.learnable_id')
                    ->where('user_progresses.learnable_type', '=', 'phrase');
            })
            ->where('user_progresses.profile_id', $profileId)
            ->where('user_progresses.repetition', '>', 0)
            ->selectRaw('phrases_categories.category_id, COUNT(DISTINCT user_progresses.learnable_id) as total')
            ->groupBy('phrases_categories.category_id')
            ->get();

        foreach ($phraseRows as $row) {
            $key = (string) $row->category_id;
            $result[$key] = ($result[$key] ?? 0) + (int) $row->total;
        }

        return $result;
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
