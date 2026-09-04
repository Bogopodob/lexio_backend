<?php

namespace App\Modules\Learning\Application\UseCases\GetStats;

use App\Modules\Learning\Domain\Entities\LanguageStat;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;

final readonly class GetStatsUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
    ) {}

    public function handle(GetStatsCommand $command): ?LanguageStat
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $stat = $this->profiles->getStat($command->profileId);

        if (! $stat) {
            return null;
        }

        $accuracy = $this->progress->accuracyStats($command->profileId);

        if ($accuracy['total'] <= 0) {
            return $stat;
        }

        return new LanguageStat(
            id: $stat->id,
            profileId: $stat->profileId,
            wordsLearned: $stat->wordsLearned,
            streakDays: $stat->streakDays,
            bestStreak: $stat->bestStreak,
            xp: $stat->xp,
            accuracy: $accuracy['correct'] / $accuracy['total'],
            lastActivityAt: $stat->lastActivityAt,
        );
    }
}
