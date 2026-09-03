<?php

namespace App\Modules\User\Application\UseCases\Profile\GetUserProfile;

final readonly class GetUserProfileCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
