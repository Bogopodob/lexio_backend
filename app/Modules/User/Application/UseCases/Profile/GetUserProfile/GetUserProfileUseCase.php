<?php

namespace App\Modules\User\Application\UseCases\Profile\GetUserProfile;

use App\Modules\User\Domain\Entities\UserProfile;
use App\Modules\User\Domain\Ports\UserProfileRepositoryInterface;

final readonly class GetUserProfileUseCase
{
    public function __construct(
        private UserProfileRepositoryInterface $userProfileRepository,
    ) {}

    public function handle(GetUserProfileCommand $command): UserProfile
    {
        return $this->userProfileRepository->findByUserId($command->userId)
            ?? new UserProfile(
                userId: $command->userId,
                name: null,
                lastname: null,
                surname: null,
                avatar: null,
            );
    }
}
