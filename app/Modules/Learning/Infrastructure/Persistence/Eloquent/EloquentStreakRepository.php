<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Entities\Streak;
use App\Modules\Learning\Domain\Ports\StreakRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserStreak as StreakModel;
use Ramsey\Uuid\Uuid;

final class EloquentStreakRepository implements StreakRepositoryInterface
{
    public function findDay(string $userId, ?string $profileId, string $date): ?Streak
    {
        $query = StreakModel::query()
            ->where('user_id', $userId)
            ->where('date', $date);

        if ($profileId === null) {
            $query->whereNull('profile_id');
        } else {
            $query->where('profile_id', $profileId);
        }

        $model = $query->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Streak $streak): Streak
    {
        $match = ['user_id' => $streak->userId, 'date' => $streak->date];

        if ($streak->profileId === null) {
            $match['profile_id'] = null;
        } else {
            $match['profile_id'] = $streak->profileId;
        }

        $model = StreakModel::query()->updateOrCreate(
            $match,
            [
                'id' => $streak->id !== '' ? $streak->id : Uuid::uuid4()->toString(),
                'words_reviewed' => $streak->wordsReviewed,
                'words_new' => $streak->wordsNew,
            ],
        );

        return $this->toDomain($model);
    }

    public function recentActiveDays(string $userId, ?string $profileId, int $limit): array
    {
        $query = StreakModel::query()
            ->where('user_id', $userId)
            ->where('words_reviewed', '>', 0)
            ->orderBy('date', 'desc')
            ->limit($limit);

        if ($profileId === null) {
            $query->whereNull('profile_id');
        } else {
            $query->where('profile_id', $profileId);
        }

        return $query->get()->map(fn (StreakModel $m) => $this->toDomain($m))->all();
    }

    private function toDomain(StreakModel $m): Streak
    {
        return new Streak(
            id: (string) $m->id,
            userId: (string) $m->user_id,
            profileId: $m->profile_id ? (string) $m->profile_id : null,
            date: (string) $m->date,
            wordsReviewed: (int) $m->words_reviewed,
            wordsNew: (int) $m->words_new,
        );
    }
}
