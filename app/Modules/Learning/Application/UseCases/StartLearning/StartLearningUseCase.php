<?php

namespace App\Modules\Learning\Application\UseCases\StartLearning;

use App\Modules\Learning\Domain\Entities\LanguageProfile;
use App\Modules\Learning\Domain\Entities\LanguageStat;
use App\Modules\Learning\Domain\Entities\ProfileWithStat;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class StartLearningUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    public function handle(StartLearningCommand $command): ProfileWithStat
    {
        $existing = $this->profiles->findByUserAndTarget($command->userId, $command->targetLanguageId);

        if ($existing) {
            return new ProfileWithStat($existing, $this->profiles->getStat($existing->id));
        }

        foreach ($this->profiles->listByUser($command->userId) as $other) {
            if ($other->isActive) {
                $this->profiles->save(new LanguageProfile(
                    id: $other->id,
                    userId: $other->userId,
                    targetLanguageId: $other->targetLanguageId,
                    nativeLanguageId: $other->nativeLanguageId,
                    level: $other->level,
                    dailyGoal: $other->dailyGoal,
                    isActive: false,
                ));
            }
        }

        $profile = $this->profiles->save(new LanguageProfile(
            id: '',
            userId: $command->userId,
            targetLanguageId: $command->targetLanguageId,
            nativeLanguageId: $command->nativeLanguageId,
            level: $command->level,
            dailyGoal: $command->dailyGoal,
            isActive: true,
        ));

        $stat = $this->profiles->saveStat(new LanguageStat(
            id: '',
            profileId: $profile->id,
            wordsLearned: 0,
            streakDays: 0,
            bestStreak: 0,
            xp: 0,
            accuracy: 0.0,
            lastActivityAt: null,
        ));

        return new ProfileWithStat($profile, $stat);
    }
}
