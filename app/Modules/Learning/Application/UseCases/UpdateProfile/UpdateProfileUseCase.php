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

        return $this->profiles->save(new LanguageProfile(
            id: $profile->id,
            userId: $profile->userId,
            targetLanguageId: $profile->targetLanguageId,
            nativeLanguageId: $profile->nativeLanguageId,
            level: $command->level ?? $profile->level,
            dailyGoal: $command->dailyGoal ?? $profile->dailyGoal,
            isActive: $command->isActive ?? $profile->isActive,
        ));
    }
}
