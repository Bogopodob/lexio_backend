<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Entities\UserGoal;
use App\Modules\Learning\Domain\Ports\GoalRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserGoal as GoalModel;
use Ramsey\Uuid\Uuid;

final class EloquentGoalRepository implements GoalRepositoryInterface
{
    public function listByProfile(string $profileId): array
    {
        return GoalModel::query()
            ->where('profile_id', $profileId)
            ->orderBy('sort')
            ->orderBy('created_at')
            ->get()
            ->map(fn (GoalModel $m) => $this->toDomain($m))
            ->all();
    }

    public function countByProfile(string $profileId): int
    {
        return GoalModel::query()->where('profile_id', $profileId)->count();
    }

    public function countCompleted(string $profileId): int
    {
        return GoalModel::query()
            ->where('profile_id', $profileId)
            ->where('progress', '>=', 100)
            ->count();
    }

    public function find(string $goalId): ?UserGoal
    {
        $model = GoalModel::query()->find($goalId);

        return $model ? $this->toDomain($model) : null;
    }

    public function save(UserGoal $goal): UserGoal
    {
        $model = GoalModel::query()->updateOrCreate(
            ['id' => $goal->id !== '' ? $goal->id : Uuid::uuid4()->toString()],
            [
                'user_id' => $goal->userId,
                'profile_id' => $goal->profileId,
                'title' => trim($goal->title),
                'desc' => $goal->desc !== null ? trim($goal->desc) : null,
                'color' => $goal->color,
                'progress' => max(0, min(100, $goal->progress)),
                'sort' => $goal->sort,
            ],
        );

        return $this->toDomain($model);
    }

    public function delete(string $goalId): void
    {
        GoalModel::query()->where('id', $goalId)->delete();
    }

    private function toDomain(GoalModel $m): UserGoal
    {
        return new UserGoal(
            id: (string) $m->id,
            userId: (string) $m->user_id,
            profileId: (string) $m->profile_id,
            title: $m->title,
            desc: $m->desc,
            color: $m->color,
            progress: (int) $m->progress,
            sort: (int) $m->sort,
        );
    }
}
