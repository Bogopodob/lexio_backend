<?php

namespace App\Modules\Learning\Application\UseCases\SubmitReview;

use App\Modules\Learning\Application\UseCases\EvaluateAchievements\EvaluateAchievementsCommand;
use App\Modules\Learning\Application\UseCases\EvaluateAchievements\EvaluateAchievementsUseCase;
use App\Modules\Learning\Domain\Entities\LanguageStat;
use App\Modules\Learning\Domain\Entities\ReviewProgress;
use App\Modules\Learning\Domain\Entities\ReviewResult;
use App\Modules\Learning\Domain\Entities\Streak;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;
use App\Modules\Learning\Domain\Ports\StreakRepositoryInterface;
use Carbon\Carbon;

final readonly class SubmitReviewUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
        private StreakRepositoryInterface $streaks,
        private EvaluateAchievementsUseCase $evaluateAchievements,
    ) {}

    public function handle(SubmitReviewCommand $command): ?ReviewResult
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $now = Carbon::now();
        $quality = max(0, min(5, $command->quality));

        $current = $this->progress->find($command->profileId, $command->learnableType, $command->learnableId);

        $easiness = $current ? $current->easinessFactor : 2.5;
        $repetition = $current ? $current->repetition : 0;
        $interval = $current ? $current->intervalDays : 0;

        if ($quality < 3) {
            $repetition = 0;
            $interval = 1;
        } else {
            $repetition++;
            $interval = $repetition === 1 ? 1 : ($repetition === 2 ? 6 : (int) round($interval * $easiness));
        }

        $easiness = max(1.3, $easiness + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02)));

        $isNewWord = ! $current || $current->repetition === 0;

        $saved = $this->progress->save(new ReviewProgress(
            id: $current ? $current->id : '',
            userId: $command->userId,
            profileId: $command->profileId,
            learnableType: $command->learnableType,
            learnableId: $command->learnableId,
            easinessFactor: $easiness,
            intervalDays: $interval,
            repetition: $repetition,
            qualityLast: $quality,
            nextReviewAt: $now->copy()->addDays($interval)->toDateTimeString(),
            lastReviewedAt: $now->toDateTimeString(),
        ));

        $xpGained = $quality * 10;

        $stat = $this->profiles->getStat($command->profileId);

        if ($stat) {
            $this->profiles->saveStat(new LanguageStat(
                id: $stat->id,
                profileId: $stat->profileId,
                wordsLearned: $stat->wordsLearned + ($isNewWord && $quality >= 3 ? 1 : 0),
                streakDays: $stat->streakDays,
                bestStreak: $stat->bestStreak,
                xp: $stat->xp + $xpGained,
                accuracy: $stat->accuracy,
                lastActivityAt: $now->toDateTimeString(),
            ));
        }

        $today = $now->copy()->startOfDay()->toDateTimeString();
        $day = $this->streaks->findDay($command->userId, $command->profileId, $today);

        $this->streaks->save(new Streak(
            id: $day ? $day->id : '',
            userId: $command->userId,
            profileId: $command->profileId,
            date: $today,
            wordsReviewed: ($day ? $day->wordsReviewed : 0) + 1,
            wordsNew: ($day ? $day->wordsNew : 0) + ($isNewWord && $quality >= 3 ? 1 : 0),
        ));

        $this->refreshStreakCounters($command->userId, $command->profileId, $now);

        $unlocked = $this->evaluateAchievements->handle(
            new EvaluateAchievementsCommand($command->profileId, $command->userId)
        ) ?? [];

        return new ReviewResult(
            $saved,
            $isNewWord,
            $xpGained,
            array_map(fn ($a) => $a->code, $unlocked),
        );
    }

    private function refreshStreakCounters(string $userId, string $profileId, Carbon $now): void
    {
        $days = $this->streaks->recentActiveDays($userId, $profileId, 400);
        $streak = 0;
        $cursor = $now->copy()->startOfDay();

        foreach ($days as $day) {
            $dayDate = Carbon::parse($day->date)->startOfDay();

            if ($dayDate->equalTo($cursor)) {
                $streak++;
                $cursor->subDay();
            } elseif ($dayDate->lessThan($cursor)) {
                break;
            }
        }

        $stat = $this->profiles->getStat($profileId);

        if (! $stat) {
            return;
        }

        $this->profiles->saveStat(new LanguageStat(
            id: $stat->id,
            profileId: $stat->profileId,
            wordsLearned: $stat->wordsLearned,
            streakDays: $streak,
            bestStreak: max($stat->bestStreak, $streak),
            xp: $stat->xp,
            accuracy: $stat->accuracy,
            lastActivityAt: $stat->lastActivityAt,
        ));
    }
}
