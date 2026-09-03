<?php

namespace App\Modules\User\Application\UseCases\Profile\UpsertUserProfile;

use App\Modules\User\Domain\Entities\UserProfile;
use App\Modules\User\Domain\Ports\UserProfileRepositoryInterface;

final readonly class UpsertUserProfileUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
    ) {}

    public function handle(UpsertUserProfileCommand $command): UserProfile
    {
        return $this->userProfileRepository->save(
            new UserProfile(
                userId: $command->userId,
                name: $command->name,
                lastname: $command->lastname,
                surname: $command->surname,
                avatar: $command->avatar,
                city: $command->city,
                birthDate: $command->birthDate,
                tags: $command->tags,
            )
        );
    }
}
