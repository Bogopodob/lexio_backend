<?php

namespace App\Modules\Learning\Application\UseCases\EvaluateAchievements;

use App\Modules\Learning\Domain\Entities\Achievement;
use App\Modules\Learning\Domain\Ports\AchievementRepositoryInterface;
use App\Modules\Learning\Domain\Ports\GoalRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;
use Carbon\Carbon;

final readonly class EvaluateAchievementsUseCase
{
    public function __construct(
        private AchievementRepositoryInterface $achievements,
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
        private GoalRepositoryInterface $goals,
    ) {}

    /**
     * Recompute progress for all achievements, unlock reached ones.
     *
     * @return list<Achievement> newly unlocked in this call
     */
    public function handle(EvaluateAchievementsCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $stat = $this->profiles->getStat($command->profileId);
        $reviewsTotal = $this->progress->countReviewed($command->profileId);
        $accuracy = $this->progress->accuracyStats($command->profileId);
        $goalsDone = $this->goals->countCompleted($command->profileId);

        $before = [];

        foreach ($this->achievements->listWithProgress($command->userId, $command->profileId) as $row) {
            $before[$row->achievement->id] = $row->unlocked;
        }

        $newlyUnlocked = [];
        $now = Carbon::now()->toDateTimeString();

        foreach ($this->achievements->all() as $achievement) {
            $value = $this->measure($achievement, $command->profileId, [
                'streak' => $stat?->streakDays ?? 0,
                'words' => $stat?->wordsLearned ?? 0,
                'xp' => $stat?->xp ?? 0,
                'reviews' => $reviewsTotal,
                'accuracy_total' => $accuracy['total'],
                'accuracy_correct' => $accuracy['correct'],
                'goals_done' => $goalsDone,
            ]);

            $reached = $value >= $achievement->ruleTarget;

            $this->achievements->saveProgress(
                $command->userId,
                $command->profileId,
                $achievement->id,
                min($value, $achievement->ruleTarget),
                $reached,
                $reached ? $now : null,
            );

            if ($reached && ! ($before[$achievement->id] ?? false)) {
                $newlyUnlocked[] = $achievement;
            }
        }

        return $newlyUnlocked;
    }

    /**
     * @param  array{streak: int, words: int, xp: int, reviews: int, accuracy_total: int, accuracy_correct: int, goals_done: int}  $m
     */
    private function measure(Achievement $achievement, string $profileId, array $m): int
    {
        return match ($achievement->ruleType) {
            'streak_days' => $m['streak'],
            'words_learned' => $m['words'],
            'xp_total' => $m['xp'],
            'reviews_total' => $m['reviews'],
            'goals_completed' => $m['goals_done'],
            'reviews_of_type' => $this->progress->countReviewedByType(
                $profileId,
                (string) ($achievement->ruleExtra['learnable_type'] ?? '')
            ),
            'accuracy' => $this->accuracyValue($achievement, $m),
            default => 0,
        };
    }

    private function accuracyValue(Achievement $achievement, array $m): int
    {
        $min = (int) ($achievement->ruleExtra['min_reviews'] ?? 1);

        if ($m['accuracy_total'] < $min) {
            return 0;
        }

        return (int) round($m['accuracy_correct'] / max(1, $m['accuracy_total']) * 100);
    }
}
