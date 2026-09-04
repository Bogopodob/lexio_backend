<?php

namespace App\Modules\Learning\Application\UseCases\UpdateProfile;

use App\Modules\Learning\Domain\Entities\LanguageProfile;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class UpdateProfileUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    public function handle(UpdateProfileCommand $command): ?LanguageProfile
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $isActive = $command->isActive ?? $profile->isActive;

        if ($isActive) {
            foreach ($this->profiles->listByUser($command->userId) as $other) {
                if ($other->id !== $profile->id && $other->isActive) {
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
        }

        return $this->profiles->save(new LanguageProfile(
            id: $profile->id,
            userId: $profile->userId,
            targetLanguageId: $profile->targetLanguageId,
            nativeLanguageId: $profile->nativeLanguageId,
            level: $command->level ?? $profile->level,
            dailyGoal: $command->dailyGoal ?? $profile->dailyGoal,
            isActive: $isActive,
        ));
    }
}
