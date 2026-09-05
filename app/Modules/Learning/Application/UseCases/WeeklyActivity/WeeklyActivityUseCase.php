<?php

namespace App\Modules\Learning\Application\UseCases\WeeklyActivity;

use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;
use Carbon\Carbon;

final readonly class WeeklyActivityUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $sessions,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    /**
     * Per-day activity, oldest first, gaps filled with zeros.
     *
     * @return list<array{date: string, minutes: int, words: int, xp: int}>|null
     */
    public function handle(WeeklyActivityCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $days = max(1, min(31, $command->days));
        $today = Carbon::now()->startOfDay();

        $buckets = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i)->toDateString();
            $buckets[$date] = ['date' => $date, 'minutes' => 0, 'words' => 0, 'xp' => 0];
        }

        foreach ($this->sessions->recentSessions($command->profileId, $days) as $row) {
            if ($row['started_at'] === null) {
                continue;
            }

            $date = Carbon::parse($row['started_at'])->toDateString();

            if (! isset($buckets[$date])) {
                continue;
            }

            $buckets[$date]['words'] += $row['answered'];
            $buckets[$date]['xp'] += $row['xp_earned'];

            if ($row['finished_at'] !== null) {
                $minutes = (int) round(
                    max(0, Carbon::parse($row['finished_at'])->getTimestamp()
                        - Carbon::parse($row['started_at'])->getTimestamp()) / 60
                );
                $buckets[$date]['minutes'] += $minutes;
            }
        }

        return array_values($buckets);
    }
}
