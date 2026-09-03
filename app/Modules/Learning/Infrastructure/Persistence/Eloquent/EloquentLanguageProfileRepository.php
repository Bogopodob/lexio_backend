<?php

namespace App\Modules\Learning\Infrastructure\Persistence\Eloquent;

use App\Modules\Learning\Domain\Entities\LanguageProfile;
use App\Modules\Learning\Domain\Entities\LanguageStat;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserLanguageProfile as ProfileModel;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserLanguageStat as StatModel;
use Ramsey\Uuid\Uuid;

final class EloquentLanguageProfileRepository implements LanguageProfileRepositoryInterface
{
    public function listByUser(string $userId): array
    {
        return ProfileModel::query()
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (ProfileModel $m) => $this->toProfile($m))
            ->all();
    }

    public function find(string $profileId): ?LanguageProfile
    {
        $model = ProfileModel::query()->find($profileId);

        return $model ? $this->toProfile($model) : null;
    }

    public function findByUserAndTarget(string $userId, string $targetLanguageId): ?LanguageProfile
    {
        $model = ProfileModel::query()
            ->where('user_id', $userId)
            ->where('target_language_id', $targetLanguageId)
            ->first();

        return $model ? $this->toProfile($model) : null;
    }

    public function save(LanguageProfile $profile): LanguageProfile
    {
        $model = ProfileModel::query()->updateOrCreate(
            ['id' => $profile->id !== '' ? $profile->id : Uuid::uuid4()->toString()],
            [
                'user_id' => $profile->userId,
                'target_language_id' => $profile->targetLanguageId,
                'native_language_id' => $profile->nativeLanguageId,
                'level' => $profile->level,
                'daily_goal' => $profile->dailyGoal,
                'is_active' => $profile->isActive,
            ],
        );

        return $this->toProfile($model);
    }

    public function getStat(string $profileId): ?LanguageStat
    {
        $model = StatModel::query()->where('profile_id', $profileId)->first();

        return $model ? $this->toStat($model) : null;
    }

    public function saveStat(LanguageStat $stat): LanguageStat
    {
        $model = StatModel::query()->updateOrCreate(
            ['profile_id' => $stat->profileId],
            [
                'words_learned' => $stat->wordsLearned,
                'streak_days' => $stat->streakDays,
                'best_streak' => $stat->bestStreak,
                'xp' => $stat->xp,
                'accuracy' => $stat->accuracy,
                'last_activity_at' => $stat->lastActivityAt,
            ],
        );

        return $this->toStat($model);
    }

    private function toProfile(ProfileModel $m): LanguageProfile
    {
        return new LanguageProfile(
            id: (string) $m->id,
            userId: (string) $m->user_id,
            targetLanguageId: (string) $m->target_language_id,
            nativeLanguageId: (string) $m->native_language_id,
            level: $m->level,
            dailyGoal: (int) $m->daily_goal,
            isActive: (bool) $m->is_active,
        );
    }

    private function toStat(StatModel $m): LanguageStat
    {
        return new LanguageStat(
            id: (string) $m->id,
            profileId: (string) $m->profile_id,
            wordsLearned: (int) $m->words_learned,
            streakDays: (int) $m->streak_days,
            bestStreak: (int) $m->best_streak,
            xp: (int) $m->xp,
            accuracy: (float) $m->accuracy,
            lastActivityAt: $m->last_activity_at ? (string) $m->last_activity_at : null,
        );
    }
}
