<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Entities\Achievement;
use App\Modules\Learning\Domain\Entities\AchievementWithProgress;
use App\Modules\Learning\Domain\Ports\AchievementRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\Achievement as AchievementModel;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserAchievement as UserAchievementModel;
use Ramsey\Uuid\Uuid;

final class EloquentAchievementRepository implements AchievementRepositoryInterface
{
    public function all(): array
    {
        return AchievementModel::query()
            ->orderBy('sort')
            ->get()
            ->map(fn (AchievementModel $m) => $this->toAchievement($m))
            ->all();
    }

    public function listWithProgress(string $userId, string $profileId): array
    {
        $progress = UserAchievementModel::query()
            ->where('user_id', $userId)
            ->where('profile_id', $profileId)
            ->get()
            ->keyBy(fn (UserAchievementModel $m) => (string) $m->achievement_id);

        $result = [];

        foreach ($this->all() as $achievement) {
            $row = $progress->get($achievement->id);

            $result[] = new AchievementWithProgress(
                achievement: $achievement,
                progress: $row ? (int) $row->progress : 0,
                unlocked: $row ? (bool) $row->unlocked : false,
                unlockedAt: $row && $row->unlocked_at ? (string) $row->unlocked_at : null,
            );
        }

        return $result;
    }

    public function saveProgress(
        string $userId,
        string $profileId,
        string $achievementId,
        int $progress,
        bool $unlocked,
        ?string $unlockedAt,
    ): void {
        $existing = UserAchievementModel::query()
            ->where('user_id', $userId)
            ->where('profile_id', $profileId)
            ->where('achievement_id', $achievementId)
            ->first();

        if ($existing) {
            // Progress never decreases; unlock is sticky.
            $existing->progress = max((int) $existing->progress, $progress);

            if ($unlocked && ! $existing->unlocked) {
                $existing->unlocked = true;
                $existing->unlocked_at = $unlockedAt;
            }

            $existing->save();

            return;
        }

        UserAchievementModel::query()->create([
            'id' => Uuid::uuid4()->toString(),
            'user_id' => $userId,
            'profile_id' => $profileId,
            'achievement_id' => $achievementId,
            'progress' => $progress,
            'unlocked' => $unlocked,
            'unlocked_at' => $unlocked ? $unlockedAt : null,
        ]);
    }

    private function toAchievement(AchievementModel $m): Achievement
    {
        return new Achievement(
            id: (string) $m->id,
            code: $m->code,
            title: $m->title,
            desc: $m->desc,
            condition: $m->condition,
            rarity: $m->rarity,
            color: $m->color,
            rewardXp: (int) $m->reward_xp,
            ruleType: $m->rule_type,
            ruleTarget: (int) $m->rule_target,
            ruleExtra: $m->rule_extra === null ? null : (array) $m->rule_extra,
        );
    }
}
